<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeWidgetCommand extends Command
{
    protected $signature = 'nexus:make:widget {name}';

    protected $description = 'Scaffold a new Nexus dashboard widget (a #[Widget] class implementing WidgetInterface)';

    public function handle(): int
    {
        $name = Str::ucfirst(Str::camel($this->argument('name')));
        $lowerName = Str::camel($name);
        $humanName = Str::headline($name);

        $widgetPath = app_path("Nexus/Widgets/{$name}");
        $classFile = "{$widgetPath}/{$name}.php";

        if (File::exists($classFile)) {
            $this->error("Widget {$name} already exists at {$classFile}");

            return self::FAILURE;
        }

        File::makeDirectory($widgetPath, 0755, true, true);

        $replacements = [
            'name' => $name,
            'lowerName' => $lowerName,
            'humanName' => $humanName,
        ];

        File::put($classFile, $this->getStub('widget_class', $replacements));

        $viewPath = resource_path('views/widgets');
        $viewFile = "{$viewPath}/{$lowerName}.blade.php";

        if (! File::exists($viewFile)) {
            File::makeDirectory($viewPath, 0755, true, true);
            File::put($viewFile, $this->getStub('widget_view', $replacements));
        }

        $this->info("Widget {$name} created at {$classFile}");
        $this->line("View: {$viewFile}");
        $this->line('Discovered automatically on the next request — no registration needed.');
        $this->line("Add '{$lowerName}' to config('nexus.dashboard.default') to show it by default, or add it via the dashboard's Customize picker.");

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
