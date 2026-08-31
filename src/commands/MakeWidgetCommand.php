<?php

namespace Nodex\Nexus\commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeWidgetCommand extends Command
{
    protected $signature = 'nexus:make:widget {name}';
    protected $description = 'Create a new Nexus widget';

    public function handle()
    {
        $name = Str::ucfirst($this->argument('name'));
        if (!Str::endsWith($name, 'Widget')) {
            $name .= 'Widget';
        }

        $widgetPath = app_path("Nexus/Widgets/{$name}");
        $viewPath = "{$widgetPath}/resources/views";

        if (File::exists("{$widgetPath}/{$name}.php")) {
            $this->error("Widget {$name} already exists!");
            return;
        }

        File::makeDirectory($widgetPath, 0755, true, true);
        File::makeDirectory($viewPath, 0755, true, true);

        $humanName = trim(preg_replace('/[A-Z]/', ' $0', str_replace('Widget', '', $name)));

        try {
            $classContent = $this->getStub('widget_class', [
                'name'      => $name,
                'humanName' => $humanName,
            ]);

            $viewContent = $this->getStub('widget_view', [
                'name'      => Str::kebab(str_replace('Widget', '', $name)),
                'humanName' => $humanName,
            ]);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return;
        }

        File::put("{$widgetPath}/{$name}.php", $classContent);

        $viewFile = "{$viewPath}/default.blade.php";
        if (!File::exists($viewFile)) {
            File::put($viewFile, $viewContent);
        }

        $this->info("Widget {$name} created successfully!");
        $this->info("Class: {$widgetPath}/{$name}.php");
        $this->info("View: {$viewFile}");
    }

    protected function getStub($name, $replacements = [])
    {
        $stubPath = __DIR__ . "/stubs/{$name}.stub";
        if (!File::exists($stubPath)) {
            throw new \RuntimeException("Stub not found: {$stubPath}");
        }
        $content = File::get($stubPath);
        foreach ($replacements as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
}
