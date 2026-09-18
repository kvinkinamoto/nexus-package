<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'nexus:make:module {name}';

    protected $description = 'Scaffold a new Nexus admin module (model, requests, migration, lang, docs) matching the package\'s real conventions';

    public function handle(): int
    {
        $name = Str::ucfirst(Str::camel($this->argument('name')));
        $lowerName = Str::lower($name);
        $snakeName = Str::snake($name);
        $pluralSnakeName = Str::plural($snakeName);
        $pluralName = Str::plural($name);
        $modulePath = app_path("Nexus/Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Module {$name} already exists at {$modulePath}");

            return self::FAILURE;
        }

        $replacements = [
            'name' => $name,
            'lowerName' => $lowerName,
            'snakeName' => $snakeName,
            'pluralSnakeName' => $pluralSnakeName,
            'pluralName' => $pluralName,
        ];

        $migrationName = date('Y_m_d_His')."_create_{$pluralSnakeName}_table.php";

        $files = [
            "Models/{$name}.php" => $this->getStub('module_model', $replacements),
            'Requests/AdminStoreRequest.php' => $this->getStub('module_request', $replacements + ['type' => 'Store']),
            'Requests/AdminUpdateRequest.php' => $this->getStub('module_request', $replacements + ['type' => 'Update']),
            "database/migrations/{$migrationName}" => $this->getStub('module_migration', $replacements),
            'resources/lang/en/translate.php' => $this->getStub('module_lang', $replacements),
            'resources/lang/uk/translate.php' => $this->getStub('module_lang', $replacements),
            'resources/views/docs.blade.php' => $this->getStub('module_docs', $replacements),
        ];

        foreach (array_keys($files) as $path) {
            File::makeDirectory(dirname("{$modulePath}/{$path}"), 0755, true, true);
        }

        foreach ($files as $path => $content) {
            File::put("{$modulePath}/{$path}", $content);
        }

        $this->info("Module {$name} created at {$modulePath}");
        $this->line('Next steps:');
        $this->line("  1. Add the fields/relations you need to Models/{$name}.php (see the field-type reference: php artisan nexus:docs:field-types)");
        $this->line('  2. php artisan migrate');
        $this->line("  3. php artisan nexus:module:install {$name}");

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
