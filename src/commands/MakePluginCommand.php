<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePluginCommand extends Command
{
    protected $signature = 'nexus:make:plugin {name} {--module= : The name of the module to target}';

    protected $description = 'Scaffold a new Nexus plugin (a #[TargetModule]/#[Filter]/#[Action] class under app/Nexus/Plugins)';

    public function handle(): int
    {
        $name = Str::ucfirst($this->argument('name'));
        $targetModule = Str::ucfirst($this->option('module') ?: 'User');

        $pluginPath = app_path("Nexus/Plugins/{$name}");
        $classFile = "{$pluginPath}/{$name}Plugin.php";

        if (File::exists($classFile)) {
            $this->error("Plugin {$name} already exists at {$classFile}");

            return self::FAILURE;
        }

        File::makeDirectory($pluginPath, 0755, true, true);

        File::put($classFile, $this->getStub('plugin_class', [
            'name' => $name,
            'targetModule' => $targetModule,
        ]));

        $this->info("Plugin {$name} (targeting module {$targetModule}) created at {$classFile}");
        $this->line('Discovered automatically on the next request — no registration needed.');
        $this->line("Remove any #[Filter]/#[Action] method you don't need, and fill in handle()/register()/boot().");

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
