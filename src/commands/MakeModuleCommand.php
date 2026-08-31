<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeModuleCommand extends Command
{
    protected $signature = 'nexus:make:module {name}';
    protected $description = 'Create a new Nexus module';

    public function handle()
    {
        $name = Str::ucfirst($this->argument('name'));
        $snakeName = Str::snake($name);
        $pluralName = Str::plural($snakeName);
        $modulePath = app_path("Nexus/Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Module {$name} already exists!");
            return;
        }

        // Create directory structure
        $directories = [
            "",
            "Enums",
            "Fields",
            "Filters",
            "Hooks",
            "Http/Controllers",
            "Http/Middleware",
            "Listeners",
            "Models",
            "Repositories",
            "Requests",
            "Resource",
            "Services",
            "Traits",
            "database/migrations",
            "database/seeders",
            "database/factories",
            "resources/lang/en",
            "resources/lang/uk",
            "resources/views/admin",
            "routes",
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$modulePath}/{$directory}", 0755, true, true);
        }

        // Create Files
        $files = [
            "ModuleConfiguration.php" => $this->getModuleConfigStub($name),
            "Http/Controllers/AdminController.php" => $this->getAdminControllerStub($name),
            "Http/Controllers/ApiController.php" => $this->getApiControllerStub($name),
            "Models/{$name}.php" => $this->getModelStub($name),
            "Services/ModuleManager.php" => $this->getModuleManagerStub($name),
            "routes/web.php" => $this->getWebRoutesStub($name),
            "routes/api.php" => $this->getApiRoutesStub($name),
            "resources/lang/en/messages.php" => $this->getLangStub($name, 'en'),
            "resources/lang/uk/messages.php" => $this->getLangStub($name, 'uk'),
            "resources/views/admin/index.blade.php" => $this->getViewStub($name),
            "database/migrations/" . date('Y_m_d_His') . "_create_{$pluralName}_table.php" => $this->getMigrationStub($name),
            "Requests/AdminStoreRequest.php" => $this->getRequestStub($name, 'Store'),
            "Requests/AdminUpdateRequest.php" => $this->getRequestStub($name, 'Update'),

            // Example files for other directories
            "Enums/ExampleEnum.php" => $this->getEnumStub($name),
            "Fields/ExampleField.php" => $this->getFieldStub($name),
            "Filters/ExampleFilter.php" => $this->getFilterStub($name),
            "Hooks/ExampleHook.php" => $this->getHookStub($name),
            "Http/Middleware/ExampleMiddleware.php" => $this->getMiddlewareStub($name),
            "Listeners/ExampleListener.php" => $this->getListenerStub($name),
            "Repositories/ExampleRepository.php" => $this->getRepositoryStub($name),
            "Resource/{$name}Resource.php" => $this->getResourceStub($name),
            "Traits/ExampleTrait.php" => $this->getTraitStub($name),
            "database/seeders/{$name}Seeder.php" => $this->getSeederStub($name),
            "database/factories/{$name}Factory.php" => $this->getFactoryStub($name),
        ];

        foreach ($files as $path => $content) {
            File::put("{$modulePath}/{$path}", $content);
        }

        $this->info("Module {$name} created successfully with full structure and examples at {$modulePath}");
    }

    protected function getModuleConfigStub($name)
    {
        return $this->getStub('module_config', [
            'name' => $name,
            'snakeName' => Str::snake($name),
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getAdminControllerStub($name)
    {
        return $this->getStub('admin_controller', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getApiControllerStub($name)
    {
        return $this->getStub('api_controller', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getModelStub($name)
    {
        return $this->getStub('model', [
            'name' => $name,
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getModuleManagerStub($name)
    {
        return $this->getStub('module_manager', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getWebRoutesStub($name)
    {
        return $this->getStub('routes_web', [
            'lowerName' => Str::lower($name),
        ]);
    }

    protected function getApiRoutesStub($name)
    {
        return $this->getStub('routes_api');
    }

    protected function getLangStub($name, $locale)
    {
        $success = ($locale === 'uk') ? 'Операція успішна' : 'Operation successful';
        return $this->getStub('lang', [
            'name' => $name,
            'success' => $success,
        ]);
    }

    protected function getViewStub($name)
    {
        return $this->getStub('view', ['name' => $name]);
    }

    protected function getMigrationStub($name)
    {
        return $this->getStub('migration', [
            'pluralName' => Str::plural(Str::snake($name)),
        ]);
    }

    protected function getRequestStub($name, $type)
    {
        return $this->getStub('request', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
            'type' => $type,
        ]);
    }

    protected function getEnumStub($name)
    {
        return $this->getStub('enum', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getFieldStub($name)
    {
        return $this->getStub('field', [
            'name' => $name,
            'lowerName' => Str::lower($name),
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getFilterStub($name)
    {
        return $this->getStub('filter', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getHookStub($name)
    {
        return $this->getStub('hook', [
            'name' => $name,
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getMiddlewareStub($name)
    {
        return $this->getStub('middleware', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getListenerStub($name)
    {
        return $this->getStub('listener', [
            'name' => $name,
            'snakeName' => Str::snake($name),
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getRepositoryStub($name)
    {
        return $this->getStub('repository', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getResourceStub($name)
    {
        return $this->getStub('resource', [
            'name' => $name,
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getTraitStub($name)
    {
        return $this->getStub('trait', [
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getSeederStub($name)
    {
        return $this->getStub('seeder', [
            'name' => $name,
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getFactoryStub($name)
    {
        return $this->getStub('factory', [
            'name' => $name,
            'moduleNamespace' => "App\Nexus\Modules\\{$name}",
        ]);
    }

    protected function getStub($name, $replacements = [])
    {
        $stubPath = __DIR__ . "/stubs/{$name}.stub";
        if (!File::exists($stubPath)) {
            return "";
        }
        $content = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, (string) $content);
        }
        return $content;
    }

}
