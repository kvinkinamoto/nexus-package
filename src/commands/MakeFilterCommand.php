<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFilterCommand extends Command
{
    protected $signature = 'nexus:make:filter {module}';

    protected $description = 'Scaffold a new Nexus module filter handler (a ModuleFilterHandler class overriding FilterHandler::filter())';

    public function handle(): int
    {
        $module = Str::ucfirst($this->argument('module'));
        $modulePath = app_path("Nexus/Modules/{$module}");

        if (! File::exists($modulePath)) {
            $this->error("Module {$module} does not exist at {$modulePath}.");

            $available = collect(File::directories(app_path('Nexus/Modules')))
                ->map(fn (string $path) => basename($path))
                ->implode(', ');

            $this->line("Available modules: {$available}");

            return self::FAILURE;
        }

        $filtersPath = "{$modulePath}/Filters";
        $classFile = "{$filtersPath}/ModuleFilterHandler.php";

        if (File::exists($classFile)) {
            $this->error("A filter handler already exists for {$module} at {$classFile}");

            return self::FAILURE;
        }

        File::makeDirectory($filtersPath, 0755, true, true);

        File::put($classFile, $this->getStub('filter_handler', [
            'module' => $module,
        ]));

        $this->info("Filter handler for {$module} created at {$classFile}");
        $this->line('Auto-resolved by name — no manual registration needed.');
        $this->line("Declare any filter you add here in {$module}'s TableConfigDto->filters too (see Dto/ModuleDtos/FilterConfigDto), or it won't appear in the admin list UI.");

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
