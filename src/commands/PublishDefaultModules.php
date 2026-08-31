<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishDefaultModules extends Command
{
    protected $signature = 'nexus:default_module:publish 
                            {--module= : Module name or comma-separated list}
                            {--force : Overwrite existing modules}';

    protected $description = 'Publish Nexus default modules';

    public function handle(): int
    {
        $this->publishModules();

        $this->info("Publish finished!");

        return self::SUCCESS;
    }

    protected function publishModules(): void
    {
        $sourcePath = $this->getVendorModulesPath();
        $targetPath = app_path('Nexus\\Modules');

        if (!File::exists($sourcePath)) {
            $this->error("Source path not found: {$sourcePath}");
            return;
        }

        File::ensureDirectoryExists($targetPath);

        $requestedModules = $this->getRequestedModules();

        $modules = File::directories($sourcePath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            if (!empty($requestedModules) && !in_array($moduleName, $requestedModules)) {
                continue;
            }

            $destination = $targetPath . '\\' . $moduleName;

            if (File::exists($destination) && !$this->option('force')) {
                $this->warn("⚠️ {$moduleName} already exists — skipped");
                continue;
            }

            File::copyDirectory($modulePath, $destination);

            $this->updateNamespace($destination, $moduleName);

            $this->info("Published: {$moduleName}");
        }
    }

    protected function getRequestedModules(): array
    {
        $option = $this->option('module');

        if (!$option) {
            return [];
        }

        return array_map('trim', explode(',', $option));
    }

    protected function getVendorModulesPath(): string
    {
        return dirname(__DIR__, 2) . '\\src\\Modules';
    }

    protected function updateNamespace(string $modulePath, string $moduleName): void
    {
        $files = File::allFiles($modulePath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = File::get($file->getPathname());

            $oldNamespace = 'Nodex\\Nexus\\Modules\\' . $moduleName;

            $newNamespace = 'App\\Nexus\\Modules\\' . $moduleName;

            $updated = str_replace($oldNamespace, $newNamespace, $content);

            File::put($file->getPathname(), $updated);
        }
    }
}
