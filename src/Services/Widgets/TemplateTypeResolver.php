<?php

namespace Nodex\Nexus\Services\Widgets;

use Illuminate\Support\Facades\Route;

/**
 * Determines the current page's templateType — the string @position() uses
 * (as its optional second argument, or via this resolver when omitted) to
 * decide which target_template a widget_assignments row must match.
 * Ported near-verbatim from the archived Widget module (this service was
 * clean/correct there); the only change is dropping the unused
 * $currentViewName view-composer path in favor of always resolving through
 * this single chain — see NexusServiceProvider's @position directive.
 *
 * Priority: manual override -> route parameter 'templateType' -> route
 * default 'templateType' -> config('nexus.widget_template_map') by route
 * name -> 'default'.
 */
class TemplateTypeResolver
{
    protected ?string $manualType = null;

    public function resolve(): string
    {
        if ($this->manualType !== null) {
            return $this->manualType;
        }

        $route = Route::current();

        if (!$route) {
            return 'default';
        }

        $param = $route->parameter('templateType');
        if ($param) {
            return (string) $param;
        }

        $defaults = $route->defaults;
        if (isset($defaults['templateType'])) {
            return (string) $defaults['templateType'];
        }

        $routeName = $route->getName();
        if ($routeName) {
            $map = config('nexus.widget_template_map', []);
            if (isset($map[$routeName])) {
                return (string) $map[$routeName];
            }
        }

        return 'default';
    }

    public function setCurrentType(string $type): void
    {
        $this->manualType = $type;
    }

    public function reset(): void
    {
        $this->manualType = null;
    }
}
