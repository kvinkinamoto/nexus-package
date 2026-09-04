<?php

namespace Nodex\Nexus\Services;

class PluginManager
{
    protected array $plugins = [];

    public function __construct(private ?HookManager $hookManager = null)
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
            $className = 'App\\Nexus\\Plugins\\' . basename($file, '.php');
            $this->discoverClass($className);
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
