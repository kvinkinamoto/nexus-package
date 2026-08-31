<?php

namespace Nodex\Nexus\Widgets\HelloWorldWidget;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Contracts\Widgets\RendersHtml;
use Nodex\Nexus\Contracts\Widgets\WidgetInterface;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Enums\WidgetSurface;

/**
 * Canonical built-in example widget — ported from the pre-Phase-5
 * app/Nexus/Widgets/HelloWorld (which extended the now-archived
 * AbstractWidget and could no longer even autoload). Kept deliberately
 * trivial: this is what a new widget author copies first.
 */
#[Widget(name: 'helloWorld', label: 'Hello World', surfaces: [WidgetSurface::Admin, WidgetSurface::Api, WidgetSurface::Front], icon: 'bx bx-smile', group: 'examples')]
class HelloWorldWidget implements WidgetInterface, RendersHtml
{
    public static function configFields(): array
    {
        return [
            (new FieldConfigDto('title', 'string'))->label('Widget Title')->default('Hello World'),
            (new FieldConfigDto('show_date', 'boolean'))->label('Show Current Date')->default(false),
        ];
    }

    public function getData(array $config, WidgetContext $context): array
    {
        return [
            'config' => $config,
            'title' => $config['title'] ?? 'Hello World',
            'show_date' => $config['show_date'] ?? false,
            'message' => 'Hello, world!',
        ];
    }

    public function availableViews(): array
    {
        $viewsPath = __DIR__ . '/resources/views';
        $namespace = 'widget-' . Str::kebab(basename(__DIR__));

        $views = [];
        if (File::isDirectory($viewsPath)) {
            foreach (File::files($viewsPath) as $file) {
                $viewName = str_replace('.blade.php', '', $file->getFilename());
                $views["{$namespace}::{$viewName}"] = $viewName;
            }
        }

        return $views;
    }

    public function viewFor(?string $name): ?string
    {
        $views = $this->availableViews();

        if ($name !== null && isset($views[$name])) {
            return $name;
        }

        return array_key_first($views) ?: null;
    }

    public function render(array $config, WidgetContext $context): string
    {
        $view = $this->viewFor($context->params['view'] ?? null);

        if (!$view || !view()->exists($view)) {
            return '';
        }

        return view($view, $this->getData($config, $context))->render();
    }
}
