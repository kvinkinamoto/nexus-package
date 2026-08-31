<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakePluginCommand extends Command
{
    protected $signature = 'nexus:make:plugin {name} {--module= : The name of the module to target}';
    protected $description = 'Create a new Nexus plugin';

    public function handle()
    {
        $name = Str::ucfirst($this->argument('name'));
        $targetModule = $this->option('module') ?: 'User'; // Default to User or wait for input
        $targetModule = Str::ucfirst($targetModule);

        $pluginPath = app_path("Nexus/Plugins/{$name}");

        if (File::exists($pluginPath)) {
            $this->error("Plugin {$name} already exists!");
            return;
        }

        // Create directory structure
        $directories = [
            "",
            "Resources/lang/en",
            "Resources/lang/uk",
            "Resources/routes",
            "Resources/views",
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$pluginPath}/{$directory}", 0755, true, true);
        }

        // Create Files
        $files = [
            "{$name}Plugin.php" => $this->getPluginClassStub($name, $targetModule),
            "Resources/routes/web.php" => $this->getStub('plugin_routes_web', ['lowerName' => Str::lower($name)]),
            "Resources/lang/en/messages.php" => $this->getStub('plugin_lang', [
                'name' => $name,
                'targetModule' => $targetModule,
                'success' => 'Plugin action successful'
            ]),
            "Resources/lang/uk/messages.php" => $this->getStub('plugin_lang', [
                'name' => $name,
                'targetModule' => $targetModule,
                'success' => 'Дія плагіна успішна'
            ]),
            "Resources/views/index.blade.php" => $this->getStub('plugin_view', [
                'name' => $name,
                'targetModule' => $targetModule
            ]),
        ];

        foreach ($files as $path => $content) {
            $content = str_replace('{{name}}', $name, (string) $content);
            File::put("{$pluginPath}/{$path}", $content);
        }

        $this->info("Plugin {$name} targeting module {$targetModule} created successfully at {$pluginPath}");
    }

    protected function getPluginClassStub($name, $targetModule)
    {
        return $this->getStub('plugin_class', [
            'name' => $name,
            'targetModule' => $targetModule,
            'lowerName' => Str::lower($name),
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
