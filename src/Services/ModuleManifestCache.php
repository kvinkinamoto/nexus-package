<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Compiles the per-module filesystem discovery that NexusServiceProvider::boot()
 * would otherwise redo on every single request (is_dir/is_file checks for views,
 * routes, translations, icons; File::files()+class_exists for commands;
 * File::files()+ReflectionMethod for listeners) into one PHP array file.
 *
 * Purely opt-in: as long as the cache file doesn't exist, NexusServiceProvider
 * falls back to its original live-scanning behavior unchanged. Build with
 * `php artisan nexus:module:cache`, drop with `php artisan nexus:module:clear`.
 * Only consulted for HTTP requests — console commands always scan live so
 * things like `migrate:fresh` see freshly added modules without a rebuild step.
 */
class ModuleManifestCache
{
    private ?array $manifest = null;

    public function path(): string
    {
        return base_path('bootstrap/cache/nexus-modules.php');
    }

    public function exists(): bool
    {
        return File::exists($this->path());
    }

    public function get(): ?array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if (!$this->exists()) {
            return null;
        }

        return $this->manifest = require $this->path();
    }

    /**
     * @param Collection $modules Result of ModuleRegistry::getAllModules() — keyed by lowercase name.
     */
    public function build(Collection $modules, PathManager $pathManager): array
    {
        $entries = [];

        foreach ($modules as $key => $module) {
            $entries[$key] = $this->buildModuleEntry($module, $pathManager);
        }

        return [
            'built_at' => date('Y-m-d H:i:s'),
            'modules' => $entries,
        ];
    }

    private function buildModuleEntry(array $module, PathManager $pathManager): array
    {
        $name = $module['name'];
        $isUserModule = $module['is_user_module'];

        $viewDir = $pathManager->getViewPath($name, $isUserModule);
        $routeDir = $pathManager->getRoutePath($name, $isUserModule);
        $langDir = $pathManager->getTranslationPath($name, $isUserModule);
        $iconDir = $pathManager->getIconPath($name, $isUserModule);
        $commandDir = $pathManager->getCommandPath($name, $isUserModule);
        $listenersDir = $pathManager->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'Listeners';
        $relationsDir = $pathManager->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'Relations';
        $fieldTypesDir = $pathManager->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'FieldTypes';
        $widgetsDir = $pathManager->getModulePath($name, $isUserModule) . DIRECTORY_SEPARATOR . 'Widgets';

        return [
            'name' => $name,
            'namespace' => $module['namespace'],
            'path' => $module['path'],
            'is_user_module' => $isUserModule,
            'has_view' => is_dir($viewDir),
            'has_route_web' => is_file($routeDir . DIRECTORY_SEPARATOR . 'web.php'),
            'has_route_api' => is_file($routeDir . DIRECTORY_SEPARATOR . 'api.php'),
            'has_translation' => is_dir($langDir),
            'has_icon' => is_dir($iconDir),
            'commands' => $this->discoverCommands($commandDir, $module['namespace']),
            'listeners' => $this->discoverListeners($listenersDir, $module['namespace']),
            'relations' => $this->discoverRelations($relationsDir, $module['namespace']),
            'scopes' => $this->discoverScopes($relationsDir, $module['namespace']),
            'fieldTypes' => $this->discoverFieldTypes($fieldTypesDir, $module['namespace']),
            'widgets' => $this->discoverWidgets($widgetsDir, $module['namespace']),
        ];
    }

    /**
     * Public so NexusServiceProvider can reuse the exact same discovery logic
     * for its live (uncached) fallback path — one implementation either way.
     */
    public function discoverCommands(string $dir, string $namespace): array
    {
        if (!File::exists($dir)) {
            return [];
        }

        $found = [];
        foreach (File::files($dir) as $file) {
            $className = $file->getBasename('.php');
            $fullClass = $namespace . '\\Commands\\' . $className;
            if (class_exists($fullClass)) {
                $found[] = $fullClass;
            }
        }

        return $found;
    }

    public function discoverListeners(string $dir, string $namespace): array
    {
        if (!File::exists($dir)) {
            return [];
        }

        $found = [];
        foreach (File::files($dir) as $file) {
            $className = $file->getBasename('.php');
            $fullClass = $namespace . '\\Listeners\\' . $className;

            if (!class_exists($fullClass)) {
                continue;
            }

            if (method_exists($fullClass, 'subscribe')) {
                $found[] = ['class' => $fullClass, 'type' => 'subscribe'];
                continue;
            }

            if (method_exists($fullClass, 'handle')) {
                $reflection = new \ReflectionMethod($fullClass, 'handle');
                $parameters = $reflection->getParameters();

                if (!empty($parameters)) {
                    $type = $parameters[0]->getType();
                    if ($type && !$type->isBuiltin()) {
                        $found[] = ['class' => $fullClass, 'type' => 'handle', 'event' => $type->getName()];
                    }
                }
            }
        }

        return $found;
    }

    /**
     * Scans a module's FieldTypes/ folder for classes implementing
     * FieldTypeRenderer — the second of FieldTypeRegistry's three
     * registration points (see Services/FieldTypeRegistry.php docblock).
     * The registered type name is camelCase(filename), e.g.
     * FieldTypes/PricePreview.php registers as type 'pricePreview'.
     */
    public function discoverFieldTypes(string $dir, string $namespace): array
    {
        if (!File::exists($dir)) {
            return [];
        }

        $found = [];
        foreach (File::files($dir) as $file) {
            $className = $file->getBasename('.php');
            $fullClass = $namespace . '\\FieldTypes\\' . $className;

            if (!class_exists($fullClass) || !is_subclass_of($fullClass, \Nodex\Nexus\Contracts\FieldTypeRenderer::class)) {
                continue;
            }

            $found[] = ['class' => $fullClass, 'type' => Str::camel($className)];
        }

        return $found;
    }

    /**
     * Scans a Widgets/ folder for #[Widget]-carrying classes, one
     * subdirectory per widget: {dir}/{FolderName}/{FolderName}.php resolves
     * to {namespace}\Widgets\{FolderName}\{FolderName} — same folder-per-widget
     * convention nexus:make:widget already generates (so each widget can
     * co-locate its own resources/views/). $namespace is the OWNING
     * namespace, i.e. without a trailing "\Widgets" segment; this method
     * appends that segment itself. Used for module Widgets/ folders, the
     * legacy top-level app/Nexus/Widgets root, and the package's own
     * built-in Widgets/ — same call either way (see NexusServiceProvider::loadWidgets()).
     *
     * Returns a plain scalar-array shape (not a Widget attribute instance)
     * so an entry can be safely var_export()'d into the manifest cache file;
     * NexusServiceProvider reconstructs the real object at registration time
     * via WidgetRegistry::registerFromDiscovery().
     *
     * class_exists() is wrapped in try/catch: a widget whose parent class
     * has since been removed (e.g. an archived legacy AbstractWidget) throws
     * a catchable \Error during autoloading — one broken widget folder must
     * not abort discovery for every other widget.
     */
    public function discoverWidgets(string $dir, string $namespace): array
    {
        if (!File::exists($dir)) {
            return [];
        }

        $found = [];
        foreach (File::directories($dir) as $widgetDir) {
            $folderName = basename($widgetDir);
            $fullClass = $namespace . '\\Widgets\\' . $folderName . '\\' . $folderName;

            try {
                if (!class_exists($fullClass)) {
                    continue;
                }
            } catch (\Throwable) {
                continue;
            }

            $reflection = new \ReflectionClass($fullClass);
            $attrs = $reflection->getAttributes(\Nodex\Nexus\Attributes\Widget::class);

            if (empty($attrs)) {
                continue;
            }

            /** @var \Nodex\Nexus\Attributes\Widget $meta */
            $meta = $attrs[0]->newInstance();

            $found[] = [
                'class' => $fullClass,
                'name' => $meta->name,
                'label' => $meta->label,
                'surfaces' => array_map(fn ($surface) => $surface->value, $meta->surfaces),
                'icon' => $meta->icon,
                'group' => $meta->group,
                'module' => $meta->module,
                'cacheTtl' => $meta->cacheTtl,
                'requires' => $meta->requires,
                'apiPublic' => $meta->apiPublic,
                'defaultSize' => $meta->defaultSize,
                'permission' => $meta->permission,
                'lazy' => $meta->lazy,
            ];
        }

        return $found;
    }

    /**
     * Scans a module's Relations/ folder for public static methods carrying
     * #[AttachRelation] — a relation this module attaches to ANOTHER
     * module's model. See Attributes/AttachRelation.php.
     */
    public function discoverRelations(string $dir, string $namespace): array
    {
        $found = [];

        foreach ($this->scanRelationsClasses($dir, $namespace) as $fullClass) {
            $reflection = new \ReflectionClass($fullClass);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
                foreach ($method->getAttributes(\Nodex\Nexus\Attributes\AttachRelation::class) as $attr) {
                    /** @var \Nodex\Nexus\Attributes\AttachRelation $meta */
                    $meta = $attr->newInstance();
                    $found[] = ['model' => $meta->model, 'name' => $meta->name, 'class' => $fullClass, 'method' => $method->getName()];
                }
            }
        }

        return $found;
    }

    /**
     * Scans a module's Relations/ folder for public static methods carrying
     * #[AttachScope] — a global scope this module attaches to ANOTHER
     * module's model. See Attributes/AttachScope.php.
     */
    public function discoverScopes(string $dir, string $namespace): array
    {
        $found = [];

        foreach ($this->scanRelationsClasses($dir, $namespace) as $fullClass) {
            $reflection = new \ReflectionClass($fullClass);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
                foreach ($method->getAttributes(\Nodex\Nexus\Attributes\AttachScope::class) as $attr) {
                    /** @var \Nodex\Nexus\Attributes\AttachScope $meta */
                    $meta = $attr->newInstance();
                    $found[] = ['model' => $meta->model, 'name' => $meta->name, 'class' => $fullClass, 'method' => $method->getName()];
                }
            }
        }

        return $found;
    }

    private function scanRelationsClasses(string $dir, string $namespace): array
    {
        if (!File::exists($dir)) {
            return [];
        }

        $classes = [];
        foreach (File::files($dir) as $file) {
            $fullClass = $namespace . '\\Relations\\' . $file->getBasename('.php');
            if (class_exists($fullClass)) {
                $classes[] = $fullClass;
            }
        }

        return $classes;
    }

    public function write(array $manifest): void
    {
        File::ensureDirectoryExists(dirname($this->path()));

        $export = var_export($manifest, true);
        File::put($this->path(), "<?php\n\n// Auto-generated by `php artisan nexus:module:cache`. Do not edit by hand.\nreturn {$export};\n");

        $this->manifest = null;
    }

    public function clear(): void
    {
        if ($this->exists()) {
            File::delete($this->path());
        }

        $this->manifest = null;
    }
}
