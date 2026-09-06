<?php

namespace Nodex\Nexus;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Livewire\Livewire;
use Nodex\Nexus\Contracts\MediaLibrary\MediaLibraryInterface;
use Nodex\Nexus\database\seeders\DatabaseSeeder;
use Nodex\Nexus\Events\FieldTypesRegistering;
use Nodex\Nexus\Events\GatheringValidationRules;
use Nodex\Nexus\Events\ModuleInstalled;
use Nodex\Nexus\Faker\FakerImageProvider;
use Nodex\Nexus\Listeners\SendModuleInstalledNotification;
use Nodex\Nexus\Livewire\ModuleForm;
use Nodex\Nexus\Livewire\ModuleSettingsForm;
use Nodex\Nexus\Livewire\ModuleTable;
use Nodex\Nexus\Models\Module as ModuleModel;
use Nodex\Nexus\Services\AttributeSchemaReader;
use Nodex\Nexus\Services\Blocks\BlockTypeRegistry;
use Nodex\Nexus\Services\DatabaseSettingsProvider;
use Nodex\Nexus\Services\DirectTranslationService;
use Nodex\Nexus\Services\FieldTypeRegistry;
use Nodex\Nexus\Services\FieldVisibilityEvaluator;
use Nodex\Nexus\Services\FormBuilder;
use Nodex\Nexus\Services\HookManager;
use Nodex\Nexus\Services\IconManager;
use Nodex\Nexus\Services\Interfaces\SettingsProviderInterface;
use Nodex\Nexus\Services\MediaLibrary\SpatieMediaLibraryService;
use Nodex\Nexus\Services\ModuleDependencyChecker;
use Nodex\Nexus\Services\ModuleManager;
use Nodex\Nexus\Services\ModuleManifestCache;
use Nodex\Nexus\Services\ModuleRegistry;
use Nodex\Nexus\Services\ModuleServiceForAdminPanel;
use Nodex\Nexus\Services\PathManager;
use Nodex\Nexus\Services\PluginManager;
use Nodex\Nexus\Services\RelationRegistrar;
use Nodex\Nexus\Services\Validation\NexusRuleCollector;
use Nodex\Nexus\Services\Widgets\AdminDashboardRenderer;
use Nodex\Nexus\Services\Widgets\DashboardLayoutResolver;
use Nodex\Nexus\Services\Widgets\FrontWidgetRenderer;
use Nodex\Nexus\Services\Widgets\TemplateTypeResolver;
use Nodex\Nexus\Services\Widgets\WidgetRegistry;

/**
 * Class nexusServiceProvider
 */
class NexusServiceProvider extends ServiceProvider
{
    private $modules = [];

    /**
     * Set from ModuleManifestCache::get() in boot(), only for non-console
     * requests. Null means "no cache — do live filesystem discovery",
     * exactly as before this cache existed.
     */
    private ?array $moduleManifest = null;

    public function register()
    {
        require_once __DIR__.'/helpers/functions.php';

        // Plain singletons — constructor deps (if any) are container-resolvable,
        // so no explicit factory closure is needed.
        $this->app->singleton(PathManager::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(ModuleManifestCache::class);
        $this->app->singleton(FormBuilder::class);
        $this->app->singleton(ModuleServiceForAdminPanel::class);
        $this->app->singleton(IconManager::class);
        $this->app->singleton(HookManager::class);
        $this->app->singleton(RelationRegistrar::class);
        $this->app->singleton(ModuleDependencyChecker::class);
        $this->app->singleton(PluginManager::class);
        $this->app->singleton(AttributeSchemaReader::class);
        $this->app->singleton(DirectTranslationService::class);
        $this->app->singleton(FieldTypeRegistry::class);
        $this->app->singleton(FieldVisibilityEvaluator::class);
        $this->app->singleton(WidgetRegistry::class);
        $this->app->singleton(BlockTypeRegistry::class);
        $this->app->singleton(DashboardLayoutResolver::class);
        $this->app->singleton(AdminDashboardRenderer::class);
        $this->app->singleton(FrontWidgetRenderer::class);
        $this->app->singleton(TemplateTypeResolver::class);
        $this->app->singleton(NexusRuleCollector::class);

        // Plain bind(), not singleton() — this is the interface
        // #[Field(type: 'gallery')] and Livewire\ModuleForm's gallery
        // methods depend on. An app can override it with `$this->app->bind(
        // \Nodex\Nexus\Contracts\MediaLibrary\MediaLibraryInterface::class,
        // YourOwnService::class)` in its own service provider — that
        // provider registers after this one, so the override simply wins,
        // no Nexus-specific extension point needed. See
        // MediaLibraryInterface's docblock and config('nexus.media_library.enabled').
        $this->app->bind(
            MediaLibraryInterface::class,
            SpatieMediaLibraryService::class,
        );

        // Same "app can override" shape as MediaLibraryInterface above.
        // SettingsBuilder::get()/set() (what Livewire\ModuleSettingsForm and
        // any module's own runtime code call) were previously calling into
        // this interface with nothing ever bound to it — every #[Setting(...)]
        // was read into the module config DTO but had no real storage, so
        // ::get() always fell back to the caller's own $default and ::set()
        // silently no-op'd.
        $this->app->bind(
            SettingsProviderInterface::class,
            DatabaseSettingsProvider::class,
        );

        if (class_exists(FakerGenerator::class)) {
            $this->app->singleton(FakerGenerator::class, function () {
                $faker = FakerFactory::create();
                $faker->addProvider(new FakerImageProvider($faker));

                return $faker;
            });
        }
    }

    /**
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Auto-discover and register plugins. Deliberately in boot(), not
        // register(): PluginManager::isPluginEnabled() queries the
        // nexus_plugins table, and Eloquent's connection resolver isn't set
        // yet during register() — every provider's register() runs before
        // any provider's boot(), including Laravel's own
        // DatabaseServiceProvider. Running this from register() meant every
        // DB lookup there threw "Call to a member function connection() on
        // null", was swallowed by the catch below, and silently fell back to
        // "enabled" every single time — so disabling a plugin from the admin
        // screen had no effect on actual discovery, only on what the list
        // page displayed. A broken plugin must not take the rest of the
        // admin panel down — but silently swallowing every exception here
        // (the previous behavior) meant a plugin bug had no way to ever
        // surface, not even in local dev; same problem, same fix, as
        // AttributeSchemaReader::guardAgainstMisconfiguration() and
        // NexusController::action()'s app.debug gate elsewhere in this
        // package.
        try {
            $pluginManager = $this->app->make(PluginManager::class);
            $pluginManager->autoDiscover();
            $pluginManager->registerAll();
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                throw $e;
            }
            report($e);
        }

        $shouldBeStrict = ! $this->app->isProduction();
        Model::shouldBeStrict($shouldBeStrict);

        $this->mergeConfig();
        $this->publish();
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->registerDefaultApiRateLimiter();

        // @position('name') or @position('name', $templateType) — renders every
        // active widget_assignments row for that position (see FrontWidgetRenderer).
        // Omitting templateType resolves it via TemplateTypeResolver rather than
        // the archived version's separate $currentViewName view-composer path —
        // one resolution chain instead of two competing ones.
        Blade::directive('position', function (string $expression) {
            $args = array_map('trim', explode(',', $expression, 2));
            $positionArg = $args[0];
            $templateTypeArg = $args[1] ?? null;

            $templateTypeExpr = $templateTypeArg !== null
                ? $templateTypeArg
                : 'app(\Nodex\Nexus\Services\Widgets\TemplateTypeResolver::class)->resolve()';

            return "<?php echo app(\Nodex\Nexus\Services\Widgets\FrontWidgetRenderer::class)->render({$positionArg}, {$templateTypeExpr}); ?>";
        });

        // @nexusForm('contact-slug') — embeds a Form module record's public
        // submission form anywhere in a Blade view. Silently renders nothing
        // for an unknown/inactive slug, same fail-quiet posture as
        // @position() above for a position with no active placement.
        Blade::directive('nexusForm', function (string $expression) {
            return "<?php \$__nexusForm = \Nodex\Nexus\Modules\Form\Models\Form::query()->where('slug', {$expression})->where('is_active', true)->first(); if (\$__nexusForm) { echo view('form::public.form', ['form' => \$__nexusForm])->render(); } ?>";
        });

        // @nexusBlocks($page->blocks) — renders an ordered collection of
        // PageBlock (or any #[Field(type:'blockEditor')]-backed) rows by
        // resolving each one's own public partial from its 'type'
        // discriminator. Same fail-quiet posture as @position()/@nexusForm
        // above — an unknown/missing block type is silently skipped rather
        // than erroring, since a page must keep rendering its other blocks
        // even if one type was since removed from BlockTypeRegistry.
        Blade::directive('nexusBlocks', function (string $expression) {
            return "<?php foreach (({$expression}) as \$__nexusBlock) { \$__nexusBlockView = 'nexus::public.block_types.' . \$__nexusBlock->type; if (\Illuminate\Support\Facades\View::exists(\$__nexusBlockView)) { echo view(\$__nexusBlockView, ['block' => \$__nexusBlock, 'data' => (object) (\$__nexusBlock->data ?? [])])->render(); } } ?>";
        });

        $this->registerValidationRulesFilter();

        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);

        if (! $this->app->runningInConsole()) {
            /** @var ModuleManifestCache $manifestCache */
            $manifestCache = $this->app->make(ModuleManifestCache::class);
            $this->moduleManifest = $manifestCache->get();
        }

        $this->modules = $this->moduleManifest !== null
            ? $this->enabledModulesFromManifest($this->moduleManifest)
            : $registry->getEnabledModules();

        $this->loadMigration();
        $this->loadView();
        $this->loadTranslation();
        $this->loadIcon();
        $this->loadRoute();
        $this->loadEvents();
        Event::listen(ModuleInstalled::class, SendModuleInstalledNotification::class);
        $this->loadRelations();
        $this->loadFieldTypes();
        $this->registerBuiltInFieldTypeAliases();
        $this->registerBuiltInFieldTypeDefaultRules();
        $this->loadWidgets();
        $this->loadLivewireComponents();

        $this->viewCompose();

        if ($this->app->runningInConsole()) {
            $this->registerSeeders();
        }
        $this->runCommand();

        // Boot plugins — same app.debug gate as register(), above.
        try {
            $pluginManager = $this->app->make(PluginManager::class);
            $pluginManager->bootAll();
        } catch (\Throwable $e) {
            if (config('app.debug')) {
                throw $e;
            }
            report($e);
        }

        // Third field-type registration point (see Services/FieldTypeRegistry.php
        // docblock): plugins hook this action to register/override field types
        // imperatively, after every module's own FieldTypes/ directory is loaded.
        // FieldTypesRegistering fires first — same registry, no plugin class needed.
        $fieldTypeRegistry = $this->app->make(FieldTypeRegistry::class);
        event(new FieldTypesRegistering($fieldTypeRegistry));
        nexus_action('nexus.field_types.register', $fieldTypeRegistry);
    }

    /**
     * Discovery-time module list. In console (migrations, artisan commands
     * during tests, etc.) we need every module regardless of enabled state,
     * to avoid missing fixtures and foreign-key ordering issues. Outside
     * console, $this->modules is already the enabled-only list resolved
     * above in boot().
     */
    private function modulesForDiscovery(): iterable
    {
        if ($this->app->runningInConsole()) {
            return $this->app->make(ModuleRegistry::class)->getAllModules();
        }

        return $this->modules;
    }

    /**
     * Mirrors ModuleRegistry::getEnabledModules()'s filter, but against the
     * cached manifest instead of a live directory scan — enable/disable is a
     * DB toggle, so this still needs one live query, just not the filesystem
     * scan the manifest cache exists to skip.
     */
    private function enabledModulesFromManifest(array $manifest): Collection
    {
        try {
            $enabledNames = ModuleModel::query()->where('is_enabled', 1)->pluck('name')->toArray();
            $enabledNames = array_map(fn ($n) => Str::lower($n), $enabledNames);
        } catch (\Throwable $e) {
            return collect();
        }

        return collect($manifest['modules'])->filter(function ($module) use ($enabledNames) {
            return in_array(Str::lower($module['name']), $enabledNames);
        });
    }

    protected function registerSeeders()
    {
        $this->app->booted(function () {
            if (in_array('db:seed', request()->server('argv', []))) {
                Artisan::call('db:seed', [
                    '--class' => DatabaseSeeder::class,
                ]);
            }
        });
    }

    private function mergeConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/nexus.php', 'nexus');
    }

    /**
     * REST and GraphQL both default to the 'api' middleware group
     * (config('nexus.api_middleware')/('graphql_middleware')). Laravel's
     * own skeleton doesn't define an 'api' rate limiter out of the box —
     * $middleware->throttleApi() in the app's bootstrap/app.php only
     * attaches throttle:api, it does NOT define the limiter itself. If an
     * app enables throttleApi() without separately registering
     * RateLimiter::for('api', ...), every api-group request 500s with
     * MissingRateLimiterException. Registering a sane default here means
     * that "just works" the moment an app calls throttleApi(), with no
     * boilerplate the app has to remember to add. Guarded so an app's own
     * RateLimiter::for('api', ...) (registered in a provider that boots
     * after this one) simply overrides this default — never a conflict.
     */
    private function registerDefaultApiRateLimiter(): void
    {
        if ($this->app->make(RateLimiter::class)->limiter('api')) {
            return;
        }

        \Illuminate\Support\Facades\RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    private function loadView(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'nexus');

        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);
        $modules = $this->modulesForDiscovery();

        foreach ($modules as $module) {
            $hasView = $this->moduleManifest !== null
                ? ($module['has_view'] ?? false)
                : is_dir($pathManager->getViewPath($module['name'], $module['is_user_module']));

            if ($hasView) {
                $dir = $pathManager->getViewPath($module['name'], $module['is_user_module']);
                $this->loadViewsFrom($dir, Str::lcfirst($module['name']));
            }

            // Register views for widgets inside this module (not cached — rare/cheap)
            $widgetsDir = $pathManager->getModulePath($module['name'], $module['is_user_module']).DIRECTORY_SEPARATOR.'Widgets';
            if (is_dir($widgetsDir)) {
                $widgetFolders = File::directories($widgetsDir);
                foreach ($widgetFolders as $widgetFolder) {
                    $widgetViewsDir = $widgetFolder.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views';
                    if (is_dir($widgetViewsDir)) {
                        $widgetName = basename($widgetFolder);
                        $namespace = Str::lcfirst($module['name']).'_widget_'.Str::snake($widgetName);
                        $this->loadViewsFrom($widgetViewsDir, $namespace);
                    }
                }
            }
        }

        $this->loadWidgetView();
    }

    private function loadWidgetView()
    {
        // Legacy top-level root and the package's own built-in Widgets/ —
        // same view-namespace convention ("widget-{kebab-folder}") either
        // way, so both roots share one loop.
        $this->loadWidgetViewsFromRoot(app_path('Nexus/Widgets'));
        $this->loadWidgetViewsFromRoot(__DIR__.'/Widgets');
    }

    private function loadWidgetViewsFromRoot(string $widgetsPath): void
    {
        if (File::exists($widgetsPath)) {
            $widgetDirectories = File::directories($widgetsPath);
            foreach ($widgetDirectories as $directory) {
                $widgetName = basename($directory);
                $viewsPath = $directory.'/resources/views';
                if (File::isDirectory($viewsPath)) {
                    $namespace = Str::kebab($widgetName);
                    $this->loadViewsFrom($viewsPath, "widget-{$namespace}");
                }
            }
        }
    }

    private function loadRoute(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);
        $modules = $this->modulesForDiscovery();

        foreach ($modules as $module) {
            $dir = $pathManager->getRoutePath($module['name'], $module['is_user_module']);

            if ($this->moduleManifest !== null) {
                $hasWeb = $module['has_route_web'] ?? false;
                $hasApi = $module['has_route_api'] ?? false;
            } else {
                $hasWeb = is_dir($dir) && is_file($dir.'/web.php');
                $hasApi = is_dir($dir) && is_file($dir.'/api.php');
            }

            if ($hasWeb) {
                $this->loadRoutesFrom($dir.'/web.php');
            }
            if ($hasApi) {
                $this->loadRoutesFrom($dir.'/api.php');
            }
        }
    }

    private function loadTranslation(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'nexus');

        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);
        $modules = $this->modulesForDiscovery();

        foreach ($modules as $module) {
            $hasTranslation = $this->moduleManifest !== null
                ? ($module['has_translation'] ?? false)
                : is_dir($pathManager->getTranslationPath($module['name'], $module['is_user_module']));

            if ($hasTranslation) {
                $dir = $pathManager->getTranslationPath($module['name'], $module['is_user_module']);
                $this->loadTranslationsFrom($dir, Str::lcfirst($module['name']));
            }
        }
    }

    private function loadIcon(): void
    {
        /** @var IconManager $iconManager */
        $iconManager = $this->app->make(IconManager::class);

        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);
        $modules = $this->modulesForDiscovery();

        foreach ($modules as $module) {
            $hasIcon = $this->moduleManifest !== null
                ? ($module['has_icon'] ?? false)
                : is_dir($pathManager->getIconPath($module['name'], $module['is_user_module']));

            if ($hasIcon) {
                $dir = $pathManager->getIconPath($module['name'], $module['is_user_module']);
                $iconManager->loadModuleIcons(Str::lcfirst($module['name']), $dir);
            }
        }
    }

    private function loadMigration(): void
    {
        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);

        // In console (like during tests) we load every module's migrations,
        // not just enabled ones, to avoid foreign-key ordering issues if
        // migrate:fresh/migrate runs them in an unpredictable order.
        foreach ($this->modulesForDiscovery() as $module) {
            $dir = $pathManager->getMigrationPath($module['name'], $module['is_user_module']);
            $this->loadMigrationsFrom($dir);
        }

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    private function runCommand(): void
    {
        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);

        $moduleCommands = [];
        foreach ($this->modules as $module) {
            if ($this->moduleManifest !== null) {
                $candidates = $module['commands'] ?? [];
            } else {
                /** @var ModuleManifestCache $manifestCache */
                $manifestCache = $this->app->make(ModuleManifestCache::class);
                $dir = $pathManager->getCommandPath($module['name'], $module['is_user_module']);
                $candidates = $manifestCache->discoverCommands($dir, $module['namespace']);
            }

            foreach ($candidates as $fullClass) {
                if (! $this->app->runningInConsole()) {
                    $isOnlyConsoleDefined = defined("$fullClass::isOnlyConsole");
                    if (! $isOnlyConsoleDefined) {
                        $moduleCommands[] = $fullClass;
                    }
                } else {
                    $moduleCommands[] = $fullClass;
                }
            }
        }

        // Core commands
        $dir = __DIR__.'/commands';
        $commands = [];
        if (File::exists($dir)) {
            $files = File::files($dir);
            foreach ($files as $file) {
                $className = $file->getBasename('.php');
                $commandNamespace = 'Nodex\\Nexus\\commands\\'.Str::ucfirst($className);
                if (class_exists($commandNamespace)) {
                    $commands[] = $commandNamespace;
                }
            }
        }

        $this->commands([
            ...$commands,
            ...$moduleCommands,
        ]);
    }

    protected function publish(): void
    {
        $this->publishes([
            __DIR__.'/config/nexus.php' => config_path('nexus.php'),
        ], ['nexus-config', 'nexus']);

        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], ['nexus-migrations', 'nexus']);

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/nexus'),
        ], ['nexus-views', 'nexus']);

        $this->publishes([
            __DIR__.'/resources/lang' => resource_path('lang/nexus'),
        ], ['nexus-lang', 'nexus']);

        $this->publishes([
            __DIR__.'/resources/publish' => public_path('/'),
        ], ['nexus-resources-publish', 'nexus']);

        $this->publishes([
            __DIR__.'/resources/js' => resource_path('js/nexus'),
        ], ['nexus-js', 'nexus']);

        $this->publishes([
            __DIR__.'/Modules' => app_path('/Nexus/Modules'),
        ], ['nexus-modules-publish', 'nexus']);

    }

    private function viewCompose(): void
    {
        $template = config('nexus.template');
        if (! $template) {
            return;
        }

        View::composer(
            ['nexus::'.$template.'.layouts.adminpanel'],
            function ($view) {
                $moduleServiceForAdmin = app()->make(ModuleServiceForAdminPanel::class);
                $view->with('menus', $moduleServiceForAdmin->getSideBarMenu());

                // Recomputed on every render — no session flash, nothing to
                // dismiss. Disappears on its own once the missing module(s)
                // get enabled, or once #[Module(requires:)] is edited.
                $currentModule = $view->getData()['module'] ?? null;
                $moduleName = is_object($currentModule) ? ($currentModule->name ?? null) : null;

                $missingDependencies = $moduleName
                    ? $this->app->make(ModuleDependencyChecker::class)->getMissingDependencies($moduleName)
                    : [];

                $view->with('moduleMissingDependencies', $missingDependencies);
            },
        );

        foreach ($this->modules as $module) {
            $config = ModuleManager::getModuleConfig($module['name']);

            if (isset($config->composers) && is_array($config->composers)) {
                foreach ($config->composers as $composerClass) {
                    if (class_exists($composerClass) && method_exists($composerClass, 'register')) {
                        $composerClass::register();
                    }
                }
            }
        }

    }

    private function loadEvents(): void
    {
        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);

        foreach ($this->modules as $module) {
            if (! isset($module['namespace'])) {
                continue;
            }

            if ($this->moduleManifest !== null) {
                $listeners = $module['listeners'] ?? [];
            } else {
                /** @var ModuleManifestCache $manifestCache */
                $manifestCache = $this->app->make(ModuleManifestCache::class);
                $moduleDir = $pathManager->getModulePath($module['name'], $module['is_user_module'] ?? false);
                $listeners = $manifestCache->discoverListeners($moduleDir.'/Listeners', $module['namespace']);
            }

            foreach ($listeners as $listener) {
                // Pattern 1: subscribe() — один клас реєструє кілька подій
                if ($listener['type'] === 'subscribe') {
                    Event::subscribe($listener['class']);

                    continue;
                }

                // Pattern 2: handle() — один клас на одну подію (тип з аргументу)
                if ($listener['type'] === 'handle' && isset($listener['event'])) {
                    Event::listen($listener['event'], $listener['class']);
                }
            }
        }
    }

    /**
     * Wires up #[AttachRelation]/#[AttachScope] declarations from every
     * enabled module's Relations/ folder — see Services/RelationRegistrar.php.
     */
    private function loadRelations(): void
    {
        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);

        /** @var RelationRegistrar $registrar */
        $registrar = $this->app->make(RelationRegistrar::class);

        foreach ($this->modules as $module) {
            if (! isset($module['namespace'])) {
                continue;
            }

            if ($this->moduleManifest !== null) {
                $relations = $module['relations'] ?? [];
                $scopes = $module['scopes'] ?? [];
            } else {
                /** @var ModuleManifestCache $manifestCache */
                $manifestCache = $this->app->make(ModuleManifestCache::class);
                $relationsDir = $pathManager->getModulePath($module['name'], $module['is_user_module'] ?? false).'/Relations';
                $relations = $manifestCache->discoverRelations($relationsDir, $module['namespace']);
                $scopes = $manifestCache->discoverScopes($relationsDir, $module['namespace']);
            }

            foreach ($relations as $relation) {
                $registrar->attachRelation($relation['model'], $relation['name'], $relation['class'], $relation['method']);
            }

            foreach ($scopes as $scope) {
                $registrar->attachScope($scope['model'], $scope['name'], $scope['class'], $scope['method']);
            }
        }
    }

    /**
     * Second field-type registration point (see Services/FieldTypeRegistry.php
     * docblock): registers every FieldTypeRenderer class found in each
     * enabled module's FieldTypes/ folder, keyed by camelCase(filename).
     */
    private function loadFieldTypes(): void
    {
        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);

        /** @var FieldTypeRegistry $registry */
        $registry = $this->app->make(FieldTypeRegistry::class);

        foreach ($this->modules as $module) {
            if (! isset($module['namespace'])) {
                continue;
            }

            if ($this->moduleManifest !== null) {
                $fieldTypes = $module['fieldTypes'] ?? [];
            } else {
                /** @var ModuleManifestCache $manifestCache */
                $manifestCache = $this->app->make(ModuleManifestCache::class);
                $fieldTypesDir = $pathManager->getModulePath($module['name'], $module['is_user_module'] ?? false).'/FieldTypes';
                $fieldTypes = $manifestCache->discoverFieldTypes($fieldTypesDir, $module['namespace']);
            }

            foreach ($fieldTypes as $fieldType) {
                $registry->registerClass($fieldType['type'], $fieldType['class']);
            }
        }
    }

    /**
     * The only two built-in field types whose Blade partial filename doesn't
     * match the type string a #[Field] declares — every other built-in type
     * resolves to its partial purely by convention (see
     * Services/FieldTypeRegistry.php's resolution-order docblock), so this
     * is the one place both need registering.
     */
    private function registerBuiltInFieldTypeAliases(): void
    {
        /** @var FieldTypeRegistry $registry */
        $registry = $this->app->make(FieldTypeRegistry::class);

        $registry->alias('editor', 'text');
        $registry->alias('date', 'birthday');
    }

    /**
     * Step 1 of NexusRuleCollector's merge order ("дефолти типу з реєстру") —
     * baseline rules implied by a field's type alone, before any of its own
     * $rules/$storeRules/$updateRules are layered on top. Registered here,
     * not hardcoded in the collector, so a module/plugin can override or add
     * to these via FieldTypeRegistry::registerDefaultRules() same as any
     * other registry entry.
     */
    private function registerBuiltInFieldTypeDefaultRules(): void
    {
        /** @var FieldTypeRegistry $registry */
        $registry = $this->app->make(FieldTypeRegistry::class);

        $registry->registerDefaultRules('email', ['email']);
        $registry->registerDefaultRules('number', ['numeric']);
        $registry->registerDefaultRules('date', ['date']);
        $registry->registerDefaultRules('datetime', ['date']);
        $registry->registerDefaultRules('boolean', ['boolean']);
        $registry->registerDefaultRules('image', ['string']);
        $registry->registerDefaultRules('video', ['string']);
        $registry->registerDefaultRules('phone', ['string']);
        $registry->registerDefaultRules('url', ['url']);
        $registry->registerDefaultRules('time', ['date_format:H:i']);
        $registry->registerDefaultRules('location', ['string']);
        $registry->registerDefaultRules('multiple_string', ['array']);
        $registry->registerDefaultRules('slug', ['string', 'alpha_dash']);
        $registry->registerDefaultRules('color', ['string', 'regex:/^#[0-9A-Fa-f]{6}$/']);
        $registry->registerDefaultRules('icon', ['string']);
        $registry->registerDefaultRules('file', ['string']);
        $registry->registerDefaultRules('markdown', ['string']);
        $registry->registerDefaultRules('range', ['numeric']);
        $registry->registerDefaultRules('currency', ['numeric']);
        $registry->registerDefaultRules('rating', ['integer', 'min:0']);
    }

    /**
     * Discovers #[Widget]-carrying classes from three roots and registers
     * each with WidgetRegistry (see its registerFromDiscovery() docblock for
     * why this uses a plain scalar-array shape rather than passing the
     * reflected Widget object directly — that shape is what makes the
     * per-module roots below cacheable the same way FieldTypes/ already is).
     */
    /**
     * Generic Livewire components used by any #[Module(livewire: true)]
     * module (see Attributes\Module::$livewire docblock) — one component per
     * admin surface (table, form, ...), parameterized by module name rather
     * than one class per module.
     */
    private function loadLivewireComponents(): void
    {
        Livewire::component('nexus-module-table', ModuleTable::class);
        Livewire::component('nexus-module-form', ModuleForm::class);
        Livewire::component('nexus-module-settings-form', ModuleSettingsForm::class);
    }

    /**
     * Guaranteed call site for GatheringValidationRules + nexus_filter(
     * 'nexus.validation.rules', ...) — hooks Illuminate\Contracts\Validation\Factory::resolver() (the same
     * "runs no matter what the resolved class looks like" pattern Laravel's
     * own FormRequestServiceProvider uses for validateResolved(), via
     * Container::resolving()) rather than any method on NexusFormRequest.
     * A module's dedicated AdminStoreRequest/AdminUpdateRequest can override
     * rules(), moduleRules(), or even define its own withValidator() however
     * it wants — none of that is touched, so there's nothing to keep in sync
     * and no final method that could turn into a fatal "Cannot override"
     * error for a legitimate use.
     *
     * Scoped to routes carrying a {module} param (every nexus.module.action
     * request, which is the only place a module's own validation happens) —
     * everywhere else (a plain `Validator::make()` call unrelated to any
     * Nexus module, Auth's LoginRequest, ...) $module is null and this is a
     * no-op passthrough to the stock Validator.
     *
     * Illuminate\Validation\Factory only holds one resolver at a time
     * (resolver() overwrites, doesn't stack) — safe here since nothing else
     * in this app calls it.
     */
    private function registerValidationRulesFilter(): void
    {
        $this->app->make(Factory::class)->resolver(
            function ($translator, array $data, array $rules, array $messages, array $attributes) {
                $module = request()->route('module');

                if ($module) {
                    $moduleConfig = ModuleManager::getModuleConfig($module->name);
                    $action = (string) (request()->route('action') ?? '');

                    event(new GatheringValidationRules($moduleConfig, $rules, $action));
                    $rules = nexus_filter('nexus.validation.rules', $rules, $moduleConfig, $action);
                }

                return new Validator($translator, $data, $rules, $messages, $attributes);
            }
        );
    }

    private function loadWidgets(): void
    {
        /** @var WidgetRegistry $widgetRegistry */
        $widgetRegistry = $this->app->make(WidgetRegistry::class);

        /** @var ModuleManifestCache $manifestCache */
        $manifestCache = $this->app->make(ModuleManifestCache::class);

        // Built-in (package) and legacy top-level roots aren't part of any
        // module's manifest entry — small and rare enough to always scan
        // live, same as loadWidgetView()'s handling of the legacy root.
        foreach ($manifestCache->discoverWidgets(__DIR__.'/Widgets', 'Nodex\\Nexus') as $widget) {
            $widgetRegistry->registerFromDiscovery($widget);
        }
        foreach ($manifestCache->discoverWidgets(app_path('Nexus/Widgets'), 'App\\Nexus') as $widget) {
            $widgetRegistry->registerFromDiscovery($widget);
        }

        /** @var PathManager $pathManager */
        $pathManager = $this->app->make(PathManager::class);

        foreach ($this->modules as $module) {
            if (! isset($module['namespace'])) {
                continue;
            }

            if ($this->moduleManifest !== null) {
                $widgets = $module['widgets'] ?? [];
            } else {
                $widgetsDir = $pathManager->getModulePath($module['name'], $module['is_user_module'] ?? false).'/Widgets';
                $widgets = $manifestCache->discoverWidgets($widgetsDir, $module['namespace']);
            }

            foreach ($widgets as $widget) {
                $widgetRegistry->registerFromDiscovery($widget);
            }
        }
    }
}
