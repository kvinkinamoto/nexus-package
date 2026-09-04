<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nodex\Nexus\Events\ModuleDiscoveryCompleted;
use Nodex\Nexus\Models\Module;

class ModuleRegistry
{
    private ?Collection $modules = null;
    private ?Collection $enabledModules = null;

    public function refresh(): void
    {
        $this->modules = null;
        $this->enabledModules = null;
    }

    public function __construct(private PathManager $pathManager)
    {
    }

    public function getAllModules(): Collection
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = collect();

        // Scan standard modules
        $this->scanDirectory($this->pathManager->getModulesRoot(), false, $modules);

        // Scan user modules
        $this->scanDirectory($this->pathManager->getUserModulesRoot(), true, $modules);

        // Lets a plugin register a module that isn't a real directory under
        // either root (a package shipping one purely in PHP), or hide one
        // that is.
        event(new ModuleDiscoveryCompleted($modules));
        $modules = nexus_filter('nexus.module.discovery', $modules);

        $this->modules = $modules;
        return $modules;
    }

    public function getEnabledModules(): Collection
    {
        if ($this->enabledModules !== null) {
            return $this->enabledModules;
        }

        try {
            $enabledNames = Module::query()->where('is_enabled', 1)->pluck('name')->toArray();
            $enabledNames = array_map(fn($n) => Str::lower($n), $enabledNames);

            $this->enabledModules = $this->getAllModules()->filter(function ($module) use ($enabledNames) {
                return in_array(Str::lower($module['name']), $enabledNames);
            });
        } catch (\Throwable $e) {
            // Fallback if table doesn't exist yet during install
            $this->enabledModules = collect();
        }

        return $this->enabledModules;
    }

    private function scanDirectory(string $path, bool $isUserModule, Collection $collection): void
    {
        if (!File::isDirectory($path)) {
            return;
        }

        $directories = File::directories($path);
        foreach ($directories as $dir) {
            $name = basename($dir);
            $collection->put(Str::lower($name), [
                'name' => $name,
                'path' => $dir,
                'namespace' => $this->pathManager->getModuleNamespace($name, $isUserModule),
                'is_user_module' => $isUserModule,
            ]);
        }
    }

    public function getModule(string $name): ?array
    {
        return $this->getAllModules()->get(Str::lower($name));
    }
}
