<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFieldCommand extends Command
{
    protected $signature = 'nexus:make:field {name} {--module= : The module this field type belongs to}';

    protected $description = 'Scaffold a new Nexus custom field type (a FieldTypeRenderer class under a module\'s FieldTypes/ folder)';

    public function handle(): int
    {
        $module = $this->option('module');

        if (! $module) {
            $this->error('The --module option is required.');

            return self::FAILURE;
        }

        $module = Str::ucfirst($module);
        $modulePath = app_path("Nexus/Modules/{$module}");

        if (! File::exists($modulePath)) {
            $this->error("Module {$module} does not exist at {$modulePath}.");

            $available = collect(File::directories(app_path('Nexus/Modules')))
                ->map(fn (string $path) => basename($path))
                ->implode(', ');

            $this->line("Available modules: {$available}");

            return self::FAILURE;
        }

        $name = Str::ucfirst(Str::camel($this->argument('name')));
        $lowerName = Str::camel($name);

        $fieldTypesPath = "{$modulePath}/FieldTypes";
        $classFile = "{$fieldTypesPath}/{$name}.php";

        if (File::exists($classFile)) {
            $this->error("Field type {$name} already exists at {$classFile}");

            return self::FAILURE;
        }

        File::makeDirectory($fieldTypesPath, 0755, true, true);

        $replacements = [
            'module' => $module,
            'name' => $name,
            'lowerName' => $lowerName,
        ];

        File::put($classFile, $this->getStub('field_class', $replacements));

        $viewFile = "{$fieldTypesPath}/{$lowerName}.blade.php";

        if (! File::exists($viewFile)) {
            File::put($viewFile, $this->getStub('field_view', $replacements));
        }

        $this->info("Field type {$name} created at {$classFile}");
        $this->line("View: {$viewFile}");
        $this->line("Use it with #[Field(type: '{$lowerName}', ...)] on a {$module} model property.");
        $this->line('Auto-discovered on the next request — no registration needed.');

        if (File::exists(base_path('bootstrap/cache/nexus-modules.php'))) {
            $this->line('A module cache is active — run `php artisan nexus:module:cache` to pick this up.');
        }

        return self::SUCCESS;
    }

    protected function getStub(string $name, array $replacements = []): string
    {
        $stubPath = __DIR__."/stubs/{$name}.stub";

        if (! File::exists($stubPath)) {
            return '';
        }

        $content = File::get($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{'.$key.'}}', (string) $value, $content);
        }

        return $content;
    }
}
