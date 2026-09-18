<?php

namespace Nodex\Nexus\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\AdminPanelPermissionEnum;
use Nodex\Nexus\Enums\PermissionPlacesEnum;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\AttributeSchemaReader;
use Nodex\Nexus\Services\ModuleManifestCache;
use Nodex\Nexus\Services\Widgets\WidgetRegistry;

class ModuleManager
{
    /** @var array<string, DefaultModuleConfigurationDto> */
    private array $configCache = [];

    public function __construct(
        private PathManager $pathManager,
        private ModuleRegistry $moduleRegistry,
        private ModuleManifestCache $manifestCache,
        private WidgetRegistry $widgetRegistry,
        private RelationRegistrar $relationRegistrar
    ) {
    }

    public static function getModuleConfig($name)
    {
        return app(self::class)->instanceGetModuleConfig($name);
    }

    /**
     * AttributeSchemaReader::read() does a full ReflectionClass + attribute
     * scan of the model on every call, and this method is the single
     * chokepoint every caller goes through to get a module's config
     * (view composers, the admin sidebar, TableBuilder, GlobalSearchService,
     * RelatedEntityFieldService...) — several of them per single request,
     * for every enabled module. ModuleManager is bound as a singleton, so
     * caching on $this scopes the cache correctly to one request (or one
     * test method, since the container — and this instance — is rebuilt
     * fresh each time).
     *
     * The DTO handed back is still a fresh, independent deep copy on every
     * call — via serialize()/unserialize(), not DefaultModuleConfigurationDto
     * ::fromArray() — so callers that mutate their copy (plugins via
     * PluginManager::apply(), the AdminTableBuilding event) never see or
     * affect another caller's copy. fromArray() was tried first and reverted:
     * several nested DTOs' fromArray() (e.g. FieldConfigDto — it silently
     * drops repeaterColumns) aren't complete round-trips of every property,
     * which is invisible today because every existing fromArray() call site
     * either receives a genuinely fresh plain array (JSON-decoded) or a DTO
     * instance it returns untouched (fromArray()'s `instanceof self` early
     * return) — never a "reconstruct this same DTO from itself" round-trip.
     * serialize()/unserialize() copies full object state regardless of any
     * one DTO's fromArray() completeness. It does mean a config DTO can never
     * carry a live Closure (attribute-driven config can't anyway — PHP
     * attribute arguments must be compile-time constants; only the legacy
     * fluent-builder ModuleConfiguration style could, and every module has
     * migrated off it) — verified across all registered modules.
     */
    public function instanceGetModuleConfig($name)
    {
        if (!array_key_exists($name, $this->configCache)) {
            $this->configCache[$name] = $this->resolveModuleConfig($name);
        }

        return unserialize(serialize($this->configCache[$name]));
    }

    private function resolveModuleConfig($name)
    {
        $module = $this->moduleRegistry->getModule($name);

        if (!$module) {
            return new DefaultModuleConfigurationDto();
        }

        /** @var PluginManager $pluginManager */
        $pluginManager = app(PluginManager::class);

        /** @var AttributeSchemaReader $reader */
        $reader = app(AttributeSchemaReader::class);

        // ── Strategy 1: Dedicated ModuleConfiguration class, PHP 8 Attribute-based ──
        // For modules with no own Eloquent model (config-only panels), a model
        // belonging to a vendor package, or a model shared with another module —
        // #[Module] carries an explicit `model:` (or none at all) instead of
        // being read off the model class itself.
        $className = $module['namespace'] . "\\ModuleConfiguration";
        if (class_exists($className)) {
            if (!$reader->hasModuleAttribute($className)) {
                throw new \RuntimeException(
                    "{$className} exists but has no #[Module] attribute. Nexus modules are " .
                    "configured exclusively via PHP 8 Attributes — see AttributeSchemaReader."
                );
            }

            $schemaConfig = $reader->read($className);
            if ($schemaConfig !== null) {
                return $pluginManager->apply($name, $schemaConfig);
            }
        }

        // ── Strategy 2: PHP 8 Attribute-based schema on the Model ──────────
        // If there is no ModuleConfiguration.php, scan the module's Models/ directory
        // for any Eloquent model annotated with #[Module] attribute and build the DTO from it.

        // Try conventional name first: Module "Article" → Models\Article
        $possibleModelClass = $module['namespace'] . '\\Models\\' . Str::studly($module['name'] ?? '');
        if (class_exists($possibleModelClass) && $reader->hasModuleAttribute($possibleModelClass)) {
            $schemaConfig = $reader->read($possibleModelClass);
            if ($schemaConfig !== null) {
                return $pluginManager->apply($name, $schemaConfig);
            }
        }

        // Laravel's own conventional location: a module whose directory only
        // holds auxiliary concerns (e.g. Modules/User/ has Enums, Requests,
        // UserAddress...) while the actual #[Module]-attributed model is
        // App\Models\{Name} — true for User, which can't move out of
        // app/Models without breaking every framework internal (guards,
        // factories, password reset) that assumes it lives there.
        $possibleAppModelClass = 'App\\Models\\' . Str::studly($module['name'] ?? '');
        if (class_exists($possibleAppModelClass) && $reader->hasModuleAttribute($possibleAppModelClass)) {
            $schemaConfig = $reader->read($possibleAppModelClass);
            if ($schemaConfig !== null) {
                return $pluginManager->apply($name, $schemaConfig);
            }
        }

        // Fallback: scan all PHP files in the Models/ directory
        $modelsDir = $module['path'] . DIRECTORY_SEPARATOR . 'Models';
        if (is_dir($modelsDir)) {
            $files = glob($modelsDir . DIRECTORY_SEPARATOR . '*.php');
            foreach ($files as $file) {
                $className = $module['namespace'] . '\\Models\\' . basename($file, '.php');
                if (class_exists($className) && $reader->hasModuleAttribute($className)) {
                    $schemaConfig = $reader->read($className);
                    if ($schemaConfig !== null) {
                        return $pluginManager->apply($name, $schemaConfig);
                    }
                }
            }
        }

        return new DefaultModuleConfigurationDto();
    }

    public static function getModuleAdminController(Module $module)
    {
        return self::getController($module, 'AdminController');
    }

    public static function getModuleApiController(Module $module)
    {
        return self::getController($module, 'ApiController');
    }

    private static function getController(Module $module, string $type)
    {
        /** @var self $instance */
        $instance = app(self::class);
        $regModule = $instance->moduleRegistry->getModule($module->name);

        if (!$regModule) {
            return null;
        }

        $className = $regModule['namespace'] . "\\Http\\Controllers\\" . $type;
        return class_exists($className) ? app()->make($className) : null;
    }

    public static function install($name, ?OutputInterface $output = null)
    {
        return app(self::class)->instanceInstall($name, $output);
    }

    public function instanceInstall($name, ?OutputInterface $output = null)
    {
        $this->moduleRegistry->refresh();
        $regModule = $this->moduleRegistry->getModule($name);
        if (!$regModule) {
            return;
        }

        // updateOrCreate(['name' => $name], ...) would match-or-create by
        // exact string, so installing the same module again under different
        // casing (a plausible slip — see Module::findByName()'s docblock)
        // would silently create a second row instead of finding the
        // existing one.
        if (! Module::findByName($name)) {
            // The 'Modules' module manages every other module's is_enabled
            // flag (see App\Nexus\Modules\Modules\Models\Module) — without
            // is_system it could disable itself and lock an admin out of the
            // only screen that could turn it back on. Forced here, not left
            // to a manual DB edit, so a fresh install is protected from the
            // start like every other system invariant this method sets up.
            Module::query()->create([
                'name' => $name,
                'is_system' => Str::lower($name) === 'modules',
            ]);
        }

        // A module's own migration may alter a table a base/vendor migration
        // creates (e.g. Permission's add_display_field ALTERs spatie/laravel-permission's
        // `permissions` table) — that vendor migration only exists in
        // database/migrations once published, with whatever timestamp the
        // publish happened to run at, so it can't be relied on to always sort
        // before a module's own migration. Applying whatever's outstanding in
        // the base path first (idempotent — already-run migrations are
        // skipped) guarantees those tables exist before the module-scoped
        // migrate below runs, without hardcoding which module depends on which
        // vendor package.
        Artisan::call('migrate', ['--path' => 'database/migrations'], $output);

        $migrationPath = $this->pathManager->getMigrationPath($name, $regModule['is_user_module']);

        // Relative path for Artisan migrate
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $migrationPath);

        Artisan::call('migrate', ['--path' => $relativePath], $output);

        if ($output) {
            $output->writeln(Artisan::output());
        }

        // Install module permissions
        $config = self::getModuleConfig($name);
        foreach (\Nodex\Nexus\Enums\PermissionPlacesEnum::cases() as $place) {
            $placeValue = $place->value;
            if (isset($config->permissions->{$placeValue}) && is_array($config->permissions->{$placeValue})) {
                foreach ($config->permissions->{$placeValue} as $actionName => $permissionName) {
                    if (empty($permissionName)) continue;

                    $actionLabelEn = trans("nexus::translate.actions.{$actionName}", [], 'en');
                    $actionLabelUk = trans("nexus::translate.actions.{$actionName}", [], 'uk');

                    if (str_starts_with($actionLabelEn, 'nexus::')) {
                        $actionLabelEn = ucfirst(str_replace('_', ' ', $actionName));
                    }
                    if (str_starts_with($actionLabelUk, 'nexus::')) {
                        $actionLabelUk = ucfirst(str_replace('_', ' ', $actionName));
                    }

                    $permissionClass = class_exists(\App\Nexus\Modules\Permission\Models\Permission::class)
                        ? \App\Nexus\Modules\Permission\Models\Permission::class
                        : \Spatie\Permission\Models\Permission::class;

                    $permissionClass::updateOrCreate(
                        ['name' => $permissionName],
                        [
                            'display_name' => [
                                'en' => ucfirst($name) . ': ' . $actionLabelEn,
                                'uk' => ucfirst($name) . ': ' . $actionLabelUk,
                            ]
                        ]
                    );
                }
            }
        }

        $this->registerModuleWidgetsImmediately($regModule);
        $this->registerModuleRelationsImmediately($regModule);
        $this->registerModuleTranslationsImmediately($regModule);

        event(new \Nodex\Nexus\Events\ModuleInstalled($name));
        nexus_action('nexus.module.installed', $name);
    }

    /**
     * NexusServiceProvider::loadWidgets() only scans an enabled module's own
     * Widgets/ folder at boot time — a module installed mid-request (via the
     * admin UI's "install" action, or `nexus:module:install` inside a Pest
     * test's single-boot process) would otherwise leave its widgets invisible
     * to the already-booted WidgetRegistry singleton until the next fresh
     * boot. Registering them here, right after install, closes that gap for
     * the current process without waiting on one.
     */
    private function registerModuleWidgetsImmediately(array $regModule): void
    {
        $widgetsDir = $this->pathManager->getModulePath($regModule['name'], $regModule['is_user_module']) . DIRECTORY_SEPARATOR . 'Widgets';

        foreach ($this->manifestCache->discoverWidgets($widgetsDir, $regModule['namespace']) as $widget) {
            $this->widgetRegistry->registerFromDiscovery($widget);
        }
    }

    /**
     * Same gap as registerModuleWidgetsImmediately() above, for
     * #[AttachRelation]/#[AttachScope] (see Attributes/AttachRelation.php):
     * NexusServiceProvider::loadRelations() only scans an enabled module's
     * Relations/ folder at boot time, so a module installed mid-request
     * would otherwise leave the relations/scopes it attaches onto another
     * module's model unregistered until the next fresh boot — the attached
     * model would raise "Call to undefined method" for a relation that, per
     * the freshly-installed module's own DB row, should already be active.
     */
    private function registerModuleRelationsImmediately(array $regModule): void
    {
        $relationsDir = $this->pathManager->getModulePath($regModule['name'], $regModule['is_user_module']) . DIRECTORY_SEPARATOR . 'Relations';

        foreach ($this->manifestCache->discoverRelations($relationsDir, $regModule['namespace']) as $relation) {
            $this->relationRegistrar->attachRelation($relation['model'], $relation['name'], $relation['class'], $relation['method']);
        }

        foreach ($this->manifestCache->discoverScopes($relationsDir, $regModule['namespace']) as $scope) {
            $this->relationRegistrar->attachScope($scope['model'], $scope['name'], $scope['class'], $scope['method']);
        }
    }

    /**
     * Same gap as registerModuleWidgetsImmediately()/registerModuleRelationsImmediately()
     * above, for NexusServiceProvider::loadTranslation(): a module installed
     * mid-request would otherwise have its own translate.php invisible under
     * its own '{module}::' namespace until the next fresh boot — most
     * visibly for a label an #[AttachField]/#[AttachColumn] on another
     * module points at via that namespace (see Attributes/AttachField.php),
     * since that label is read on every render, not just at install time.
     */
    private function registerModuleTranslationsImmediately(array $regModule): void
    {
        $dir = $this->pathManager->getTranslationPath($regModule['name'], $regModule['is_user_module']);

        if (is_dir($dir)) {
            app('translator')->addNamespace(Str::lcfirst($regModule['name']), $dir);
        }
    }

    public static function getModules(): array
    {
        return app(self::class)->instanceGetModules();
    }

    public function instanceGetModules(): array
    {
        $modules = [];
        foreach ($this->moduleRegistry->getAllModules() as $name => $module) {
            try {
                $moduleConfig = self::getModuleConfig($module['name']);
                if ($moduleConfig && !empty((array) $moduleConfig) && $moduleConfig->name !== 'ModuleName') {
                    $modules[] = (array) $moduleConfig;
                }
            } catch (\Throwable $exception) {
                logger()->error("Nexus: failed to load module config for \"{$module['name']}\": {$exception->getMessage()}", [
                    'exception' => $exception,
                ]);
                continue;
            }
        }
        return $modules;
    }

    public static function getClassFromModule(string $classPathName)
    {
        return app(self::class)->instanceGetClassFromModule($classPathName);
    }

    public function instanceGetClassFromModule(string $classPathName)
    {
        // This method seems to assume a specific structure.
        // We'll try to find it in both Roots.
        foreach ([$this->pathManager->getModulesRoot(), $this->pathManager->getUserModulesRoot()] as $root) {
            $fileName = $root . DIRECTORY_SEPARATOR . $classPathName . '.php';
            if (file_exists($fileName)) {
                $isUser = $root === $this->pathManager->getUserModulesRoot();
                $namespace = $isUser ? 'App\\Nexus\\UserModules\\' : 'App\\Nexus\\Modules\\';
                $className = $namespace . str_replace('/', '\\', $classPathName);
                if (class_exists($className)) {
                    return new $className();
                }
            }
        }
        return null;
    }

    public static function getInstalledModule(string $name)
    {
        return Module::findByName($name) ?? throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Module::class);
    }

    public function uninstall($name)
    {
        $module = Module::findByName($name);
        if (!$module) {
            return false;
        }

        // Prevent uninstalling system modules
        if ($module->is_system) {
            return false;
        }

        // Delete permissions generated for this module
        $config = self::getModuleConfig($name);
        foreach (\Nodex\Nexus\Enums\PermissionPlacesEnum::cases() as $place) {
            $placeValue = $place->value;
            if (isset($config->permissions->{$placeValue}) && is_array($config->permissions->{$placeValue})) {
                foreach ($config->permissions->{$placeValue} as $actionName => $permissionName) {
                    if (empty($permissionName)) continue;

                    $permissionClass = class_exists(\App\Nexus\Modules\Permission\Models\Permission::class)
                        ? \App\Nexus\Modules\Permission\Models\Permission::class
                        : \Spatie\Permission\Models\Permission::class;

                    $permissionClass::where('name', $permissionName)->delete();
                }
            }
        }

        event(new \Nodex\Nexus\Events\ModuleUninstalled($name));
        nexus_action('nexus.module.uninstalled', $name);

        return $module->delete();
    }

    public static function getEnabledModules()
    {
        return app(self::class)->instanceGetEnabledModules();
    }

    public function instanceGetEnabledModules()
    {
        try {
            return Module::query()->where('is_enabled', 1)->get();
        } catch (\Throwable $exception) {
            if (!Schema::hasTable((new Module())->getTable())) {
                Artisan::call('migrate', [], null);
            }
            return Module::query()->where('is_enabled', 1)->get();
        }
    }

    public static function getModuleSeeders()
    {
        return app(self::class)->instanceGetModuleSeeders();
    }

    public function instanceGetModuleSeeders()
    {
        $seeders = [];
        $modules = $this->getEnabledModules();
        foreach ($modules as $module) {
            $regModule = $this->moduleRegistry->getModule($module->name);
            if (!$regModule)
                continue;

            $className = $regModule['namespace'] . "\\Database\\Seeders\\DatabaseSeeder";
            if (class_exists($className)) {
                $seeders[] = $className;
            }
        }
        return $seeders;
    }

    public static function checkPermission(string $action, ?Module $module = null, ?string $place = PermissionPlacesEnum::ADMIN_PANEL->value)
    {
        return \Nodex\Nexus\Http\Actions\CheckUserPermissionAction::handle($action, $module, $place);
    }

    public static function nexus_module_class(string $module, string $class): string
    {
        $app = "App\\Nexus\\Modules\\{$module}\\{$class}";
        $vendor = "Nodex\\Nexus\\Modules\\{$module}\\{$class}";

        if (class_exists($app)) {
            return $app;
        }

        if (class_exists($vendor)) {
            return $vendor;
        }

        throw new Exception("Class not found: {$module}\\{$class}");
    }

    public function storeCustomRelation(string $name, array $data, \Illuminate\Database\Eloquent\Model $model)
    {
        // Fallback for modules that don't have their own manager
    }
}
