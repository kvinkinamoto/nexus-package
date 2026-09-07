<?php

namespace Nodex\Nexus\Services;

use Nodex\Nexus\Attributes\AttachColumn;
use Nodex\Nexus\Attributes\AttachField;
use Nodex\Nexus\Attributes\Relation as RelationAttr;
use Nodex\Nexus\Dto\ModuleDtos\ColumnConfigDto;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;
use ReflectionClass;
use ReflectionMethod;

class PluginManager
{
    protected array $plugins = [];

    /** @var array<string, array<string, FieldConfigDto>> Target module name => field name => config, from #[AttachField]. */
    protected array $fieldAttachments = [];

    /** @var array<string, array<string, \Nodex\Nexus\Dto\ModuleDtos\RelationConfigDto>> Target module name => relation name => config, from #[AttachField]+#[Relation]. */
    protected array $relationAttachments = [];

    /** @var array<string, array<string, ColumnConfigDto>> Target module name => column name => config, from #[AttachColumn]. */
    protected array $columnAttachments = [];

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
        $targetModule = ucfirst($targetModule);
        $this->plugins[$targetModule][] = $pluginClass;
    }

    /**
     * Get all plugins registered for a module.
     */
    public function getPlugins(string $targetModule): array
    {
        $targetModule = ucfirst($targetModule);
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
        $targetModule = ucfirst($targetModule);

        // Declarative #[AttachField]/#[AttachColumn] attachments first, so a
        // plugin's own handle() (the escape hatch for anything they can't
        // express) can still see and override/remove them afterward.
        foreach ($this->fieldAttachments[$targetModule] ?? [] as $name => $field) {
            $configuration->form->fields[$name] = $field;
        }

        foreach ($this->relationAttachments[$targetModule] ?? [] as $name => $relation) {
            $configuration->relations->is_available[$name] = $relation;
        }

        foreach ($this->columnAttachments[$targetModule] ?? [] as $name => $column) {
            $configuration->table->columns[$name] = $column;
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
     * #[AttachColumn] marker methods — declarative sugar over handle() for
     * the common case of adding one field/column (optionally paired with
     * #[Relation] for a relation-backed field) to the target module's admin
     * schema without editing that module's own file. See
     * Attributes/AttachField.php, Attributes/AttachColumn.php.
     */
    private function discoverFieldAttachments(ReflectionClass $reflection, string $targetModule): void
    {
        $targetModule = ucfirst($targetModule);

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
                );
                $field->order = $meta->order;

                $this->fieldAttachments[$targetModule][$meta->name] = $field;

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

                $this->columnAttachments[$targetModule][$meta->name] = $column;
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
