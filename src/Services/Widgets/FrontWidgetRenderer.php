<?php

namespace Nodex\Nexus\Services\Widgets;

use Nodex\Nexus\Models\WidgetAssignment;

/**
 * Backs the @position() Blade directive (see NexusServiceProvider::boot()).
 * Ported from the archived Widget module's WidgetRenderer, adapted to the
 * new WidgetInstance::render() (which itself goes through WidgetRegistry +
 * WidgetContext instead of the old raw FQCN + view-name pair).
 */
class FrontWidgetRenderer
{
    /** Per-request cache so the same position isn't queried twice in one response. */
    protected array $cache = [];

    public function render(string $position, string $templateType = 'default'): string
    {
        $cacheKey = "{$position}::{$templateType}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $assignments = WidgetAssignment::query()
            ->with(['widgetInstance' => fn ($q) => $q->where('is_enabled', true)])
            ->forPosition($position, $templateType)
            ->orderBy('sort_order')
            ->get();

        $html = '';

        foreach ($assignments as $assignment) {
            $instance = $assignment->widgetInstance;

            if (!$instance || !$instance->is_enabled) {
                continue;
            }

            $html .= $instance->render();
        }

        return $this->cache[$cacheKey] = $html;
    }

    public function flushCache(): void
    {
        $this->cache = [];
    }
}
