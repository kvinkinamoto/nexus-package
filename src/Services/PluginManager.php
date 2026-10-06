<?php

namespace Nodex\Nexus\Services;

use Nodex\Nexus\Attributes\AttachColumn;
use Nodex\Nexus\Attributes\AttachField;
use Nodex\Nexus\Attributes\AttachFilter;
use Nodex\Nexus\Attributes\Relation as RelationAttr;
use Nodex\Nexus\Dto\ModuleDtos\ColumnConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\FilterConfigDto;
use Nodex\Nexus\Http\Actions\CheckUserPermissionAction;
use ReflectionClass;
use ReflectionMethod;

class PluginManager
{
    protected array $plugins = [];

    /**
     * @var array<string, array<string, array{dto: FieldConfigDto, permission: ?string}>>
     *   Target module name => field name => entry, from #[AttachField].
     */
    protected array $fieldAttachments = [];

    /** @var array<string, array<string, \Nodex\Nexus\Dto\ModuleDtos\RelationConfigDto>> Target module name => relation name => config, from #[AttachField]+#[Relation]. */
    protected array $relationAttachments = [];

    /**
     * @var array<string, array<string, array{dto: ColumnConfigDto, permission: ?string}>>
     *   Target module name => column name => entry, from #[AttachColumn].
     */
    protected array $columnAttachments = [];

    /**
     * @var array<string, array<string, array{dto: FilterConfigDto, permission: ?string}>>
     *   Target module name => filter name => entry, from #[AttachFilter].
     */
    protected array $filterAttachments = [];

    public function __construct(private ?HookManager $hookManager = null, private ?AttributeSchemaReader $schemaReader = null)
    {
    }

    /**
     * Register a plugin for a specific module.
     * 
     * @param string $targetModule The name of the module to target (e.g. 'auth')
     * @param string $pluginClass The class name of the plugin
     */
    public function register(string $targetModule, string $pluginClass): void
    {
        $targetModule = strtolower($targetModule);
        $this->plugins[$targetModule][] = $pluginClass;
    }

    /**
     * Get all plugins registered for a module.
     */
    public function getPlugins(string $targetModule): array
    {
        $targetModule = strtolower($targetModule);
        return $this->plugins[$targetModule] ?? [];
    }

    /**
     * Apply plugins to a module configuration object.
     * 
     * @param string $targetModule
     * @param object $configuration The configuration object (usually ModuleConfiguration)
     * @return object The modified configuration
     */
    public function apply(string $targetModule, object $configuration): object
    {
        $targetModule = strtolower($targetModule);

        // Declarative #[AttachField]/#[AttachColumn]/#[AttachFilter]
        // attachments first, so a plugin's own handle() (the escape hatch
        // for anything they can't express) can still see and override/
        // remove them afterward. Each entry's own `permission` (if set)
        // gates it independently of whatever the target module's own
        // action already requires — an entry the current viewer can't see
        // is simply never merged in, not hidden client-side.
        foreach ($this->fieldAttachments[$targetModule] ?? [] as $name => $entry) {
            if ($this->canSeeAttachment($entry['permission'])) {
                $configuration->form->fields[$name] = $entry['dto'];
            }
        }

        foreach ($this->relationAttachments[$targetModule] ?? [] as $name => $relation) {
            // Relation config only matters for a field that's actually
            // visible — an #[AttachField] the viewer lacks permission for
            // never entered $configuration->form->fields above, so its
            // relation config shouldn't leak into $configuration->relations
            // either (ManagesRelationPicker/AjaxController resolve relation
            // data straight from this map, independent of the form fields
            // loop above).
            if (isset($configuration->form->fields[$name])) {
                $configuration->relations->is_available[$name] = $relation;
            }
        }

        foreach ($this->columnAttachments[$targetModule] ?? [] as $name => $entry) {
            if ($this->canSeeAttachment($entry['permission'])) {
                $configuration->table->columns[$name] = $entry['dto'];
            }
        }

        foreach ($this->filterAttachments[$targetModule] ?? [] as $name => $entry) {
            if ($this->canSeeAttachment($entry['permission'])) {
                $configuration->table->filters[$name] = $entry['dto'];
            }
        }

        $plugins = $this->getPlugins($targetModule);

        foreach ($plugins as $pluginClass) {
            if (class_exists($pluginClass)) {
                $plugin = app($pluginClass);
                if (method_exists($plugin, 'handle')) {
                    $plugin->handle($configuration);
                }
            }
        }

        return $configuration;
    }

    /**
     * Validation rules of the plain (non-relation) #[AttachField]s on a target
     * module that the current viewer can see, keyed by field name. Used to
     * keep their submitted values through validated() and to write them to
     * the model (see fillAttachedFields()).
     *
     * @return array<string, array>
     */
    public function attachedFieldRules(string $targetModule): array
    {
        $targetModule = strtolower($targetModule);
        $rules = [];

        foreach ($this->fieldAttachments[$targetModule] ?? [] as $name => $entry) {
            if (!$this->canSeeAttachment($entry['permission'])) {
                continue;
            }

            $relation = $this->relationAttachments[$targetModule][$name] ?? null;

            if ($relation) {
                // Relation pickers post "relation.{name}"; a read-only relationManager posts nothing.
                if ($entry['dto']->type === 'relation') {
                    $multiple = in_array($relation->type, [
                        \Nodex\Nexus\Enums\RelationConfigParamsEnum::BELONGS_TO_MANY->value,
                        \Nodex\Nexus\Enums\RelationConfigParamsEnum::HAS_MANY->value,
                    ], true);
                    $rules["relation.{$name}"] = array_filter([$entry['required'] ? 'required' : 'nullable', $multiple ? 'array' : null]);
                }

                continue;
            }

            $fieldRules = is_string($entry['rules']) ? explode('|', $entry['rules']) : (array) $entry['rules'];
            $rules[$name] = $fieldRules ?: [$entry['required'] ? 'required' : 'nullable'];
        }

        return $rules;
    }

    /**
     * Validates the submitted values of the target module's plain attached
     * columns on their own (the target module's Request/rules don't know
     * them — and under Livewire its validation never sees the module route)
     * and returns $validated extended with the result.
     */
    public function mergeAttachedFields(array $validated, array $input, string $targetModule): array
    {
        $rules = $this->attachedFieldRules($targetModule);

        if ($rules === []) {
            return $validated;
        }

        return array_replace_recursive($validated, \Illuminate\Support\Facades\Validator::make($input, $rules)->validate());
    }

    /** True when $name is a field/relation another module attached to $targetModule (and the viewer can see it). */
    public function isAttachedField(string $targetModule, string $name): bool
    {
        $rules = $this->attachedFieldRules($targetModule);

        return isset($rules[$name]) || isset($rules["relation.{$name}"]);
    }

    /**
     * Writes the validated values of the target module's plain attached
     * columns onto the model, bypassing $fillable (the target module doesn't
     * know the column — it belongs to the attaching module).
     */
    public function fillAttachedFields(\Illuminate\Database\Eloquent\Model $model, array $validated, string $targetModule): void
    {
        $values = array_intersect_key($validated, $this->attachedFieldRules($targetModule));

        if ($values !== []) {
            $model->forceFill($values);
        }
    }

    /**
     * Gate for an #[AttachField]/#[AttachColumn]/#[AttachFilter]'s optional
     * `permission`. Null means "no extra gate" — always visible (the
     * pre-existing, backward-compatible behavior). A super-admin
     * (AdminPanelPermissionEnum::ALL) always passes, matching
     * CheckUserPermissionAction's own convention. Fails closed (hidden) for
     * a guest or a permission name that doesn't exist yet, never a 500 —
     * see CheckUserPermissionAction::hasPermission()'s own docblock for why.
     */
    private function canSeeAttachment(?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return CheckUserPermissionAction::hasPermission($user, \Nodex\Nexus\Enums\AdminPanelPermissionEnum::ALL->value)
            || CheckUserPermissionAction::hasPermission($user, $permission);
    }

    /**
     * Automatically discover plugins in the default directory.
     */
    public function autoDiscover(): void
    {
        $pluginsPath = app_path('Nexus/Plugins');

        if (!is_dir($pluginsPath)) {
            return;
        }

        // Scan for subdirectories
        $directories = glob($pluginsPath . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $pluginDirName = basename($dir);

            if (!$this->isPluginEnabled($pluginDirName)) {
                continue;
            }

            // Look for PHP files in the plugin directory
            $files = glob($dir . '/*.php');

            foreach ($files as $file) {
                $fileName = basename($file, '.php');
                $className = 'App\\Nexus\\Plugins\\' . $pluginDirName . '\\' . $fileName;
                $this->discoverClass($className);
            }
        }

        // Also check the root directory for backward compatibility or simple plugins
        $files = glob($pluginsPath . '/*.php');
        foreach ($files as $file) {
            $pluginName = basename($file, '.php');

            if (!$this->isPluginEnabled($pluginName)) {
                continue;
            }

            $className = 'App\\Nexus\\Plugins\\' . $pluginName;
            $this->discoverClass($className);
        }

        $this->discoverModuleAdminAttachments();
    }

    /**
     * A module that extends another module's admin form/table (a "dependent"
     * module — e.g. BlogPost adding the "Posts" list to BlogCategory) can ship
     * that extension itself: any class under {module}/Admin/ carrying
     * #[TargetModule] + #[AttachField]/#[AttachColumn]/#[AttachFilter] is
     * discovered exactly like a plugin class, but only while the declaring
     * module is enabled — no separate plugin to install or toggle.
     */
    private function discoverModuleAdminAttachments(): void
    {
        try {
            $modules = app(ModuleRegistry::class)->getEnabledModules();
        } catch (\Throwable) {
            return;
        }

        foreach ($modules as $module) {
            $dir = $module['path'] . DIRECTORY_SEPARATOR . 'Admin';

            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob($dir . '/*.php') ?: [] as $file) {
                $this->discoverClass($module['namespace'] . '\\Admin\\' . basename($file, '.php'));
            }
        }
    }

    /**
     * Folder-level (or loose top-level file) enable/disable, backed by the
     * `nexus_plugins` table (see App\Nexus\Modules\Plugins\Models\Plugin) —
     * the admin-facing counterpart to the class-level
     * config('nexus.plugins.disabled') check in discoverClass() below, which
     * still runs independently and covers the individual-class case.
     * firstOrCreate() means a newly added plugin folder shows up in the
     * admin list (enabled by default) the next time it's discovered, with no
     * separate install step. Fails open (enabled) on any error — e.g. the
     * table not migrated yet on a fresh install — so a missing/pending
     * migration never silently disables every plugin, matching
     * ModuleRegistry::getEnabledModules()'s own fail-open precedent.
     */
    private function isPluginEnabled(string $name): bool
    {
        if (!class_exists(\App\Nexus\Modules\Plugins\Models\Plugin::class)) {
            return true;
        }

        try {
            $plugin = \App\Nexus\Modules\Plugins\Models\Plugin::findByName($name)
                ?? \App\Nexus\Modules\Plugins\Models\Plugin::create(['name' => $name, 'is_enabled' => true]);

            return (bool) $plugin->is_enabled;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Registers a discovered plugin class against #[TargetModule] (config
     * mutation, existing behavior) and, independently, against any
     * #[Filter]/#[Action] hook attributes on its methods (see
     * Attributes/Filter.php, Attributes/Action.php, HookManager). A class
     * can carry either, both, or neither.
     */
    private function discoverClass(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        if (in_array($className, config('nexus.plugins.disabled', []), true)) {
            return;
        }

        $reflection = new \ReflectionClass($className);

        $targetModuleAttrs = $reflection->getAttributes(\Nodex\Nexus\Attributes\TargetModule::class);
        if (!empty($targetModuleAttrs)) {
            $targetModule = $targetModuleAttrs[0]->newInstance()->name;
            $this->register($targetModule, $className);
            $this->discoverFieldAttachments($reflection, $targetModule);
        }

        if (!$this->hookManager) {
            return;
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(\Nodex\Nexus\Attributes\Filter::class) as $attr) {
                /** @var \Nodex\Nexus\Attributes\Filter $meta */
                $meta = $attr->newInstance();
                $this->hookManager->addFilter($meta->hook, [$className, $method->getName()], $meta->priority);
            }

            foreach ($method->getAttributes(\Nodex\Nexus\Attributes\Action::class) as $attr) {
                /** @var \Nodex\Nexus\Attributes\Action $meta */
                $meta = $attr->newInstance();
                $this->hookManager->addAction($meta->hook, [$className, $method->getName()], $meta->priority);
            }
        }
    }

    /**
     * Scans a #[TargetModule]-carrying plugin class for #[AttachField]/
     * #[AttachColumn]/#[AttachFilter] marker methods — declarative sugar
     * over handle() for the common case of adding one field/column/filter
     * (optionally paired with #[Relation] for a relation-backed field) to
     * the target module's admin schema without editing that module's own
     * file. See Attributes/AttachField.php, Attributes/AttachColumn.php,
     * Attributes/AttachFilter.php. Each entry's `permission` is kept
     * alongside its built DTO rather than merged into $configuration here —
     * that only happens in apply(), which runs per-request with the actual
     * viewer resolved, unlike this discovery pass (see canSeeAttachment()'s
     * docblock).
     */
    private function discoverFieldAttachments(ReflectionClass $reflection, string $targetModule): void
    {
        $targetModule = strtolower($targetModule);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(AttachField::class) as $attr) {
                /** @var AttachField $meta */
                $meta = $attr->newInstance();

                $field = new FieldConfigDto(
                    name: $meta->name,
                    type: $meta->type,
                    section: $meta->section,
                    label: $meta->label,
                    isRequired: $meta->isRequired,
                    apiExpose: $meta->apiExpose,
                );
                $field->order = $meta->order;

                $this->fieldAttachments[$targetModule][$meta->name] = [
                    'dto' => $field,
                    'permission' => $meta->permission,
                    'rules' => $meta->rules,
                    'required' => $meta->isRequired,
                ];

                $relationAttrs = $method->getAttributes(RelationAttr::class);
                if (!empty($relationAttrs)) {
                    /** @var RelationAttr $relMeta */
                    $relMeta = $relationAttrs[0]->newInstance();
                    $schemaReader = $this->schemaReader ?? app(AttributeSchemaReader::class);
                    $this->relationAttachments[$targetModule][$meta->name] = $schemaReader->buildRelationConfigDto($relMeta, $meta->name);
                }
            }

            foreach ($method->getAttributes(AttachColumn::class) as $attr) {
                /** @var AttachColumn $meta */
                $meta = $attr->newInstance();

                $column = new ColumnConfigDto(
                    name: $meta->name,
                    label: $meta->label,
                    sortable: $meta->sortable,
                    tableDefault: $meta->tableDefault,
                );
                $column->order = $meta->order;

                $this->columnAttachments[$targetModule][$meta->name] = ['dto' => $column, 'permission' => $meta->permission];
            }

            foreach ($method->getAttributes(AttachFilter::class) as $attr) {
                /** @var AttachFilter $meta */
                $meta = $attr->newInstance();

                $filter = new FilterConfigDto($meta->name, $meta->label, $meta->type);

                $this->filterAttachments[$targetModule][$meta->name] = ['dto' => $filter, 'permission' => $meta->permission];
            }
        }
    }

    /**
     * Call register() on all registered plugins.
     */
    public function registerAll(): void
    {
        foreach ($this->plugins as $module => $plugins) {
            foreach ($plugins as $pluginClass) {
                if (method_exists($pluginClass, 'register')) {
                    app()->call([new $pluginClass, 'register']);
                }
            }
        }
    }

    /**
     * Call boot() on all registered plugins.
     */
    public function bootAll(): void
    {
        foreach ($this->plugins as $module => $plugins) {
            foreach ($plugins as $pluginClass) {
                if (method_exists($pluginClass, 'boot')) {
                    app()->call([new $pluginClass, 'boot']);
                }
            }
        }
    }
}
