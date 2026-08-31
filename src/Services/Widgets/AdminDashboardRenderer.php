<?php

namespace Nodex\Nexus\Services\Widgets;

use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Contracts\Widgets\ProvidesMetric;
use Nodex\Nexus\Contracts\Widgets\RendersHtml;
use Nodex\Nexus\Dto\Widgets\MetricDto;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Enums\WidgetSurface;

/**
 * Turns a resolved list of widget keys (see DashboardLayoutResolver) into
 * rendered HTML cards for pages/dashboard.blade.php. A key that no longer
 * resolves in WidgetRegistry (widget uninstalled/module disabled since the
 * layout was saved) or no longer declares the Admin surface is skipped
 * silently — a stale saved layout must never break the dashboard.
 */
class AdminDashboardRenderer
{
    public function __construct(
        private WidgetRegistry $registry,
        private WidgetOutputCache $outputCache,
    ) {
    }

    /**
     * @param string[] $widgetKeys
     * @return array<int, array{key: string, meta: Widget, html: string}>
     */
    public function render(array $widgetKeys, WidgetContext $context): array
    {
        $cards = [];

        foreach ($widgetKeys as $key) {
            $entry = $this->registry->find($key);

            if (!$entry || !in_array(WidgetSurface::Admin, $entry['meta']->surfaces, true)) {
                continue;
            }

            if (!WidgetPermissionChecker::check($entry['meta'], $context->user)) {
                continue;
            }

            $cards[] = [
                'key' => $key,
                'meta' => $entry['meta'],
                'html' => $entry['meta']->lazy ? $this->renderPlaceholder($entry['meta']) : $this->renderOne($entry, $context),
            ];
        }

        return $cards;
    }

    /**
     * Renders a single card's real HTML, bypassing the lazy placeholder —
     * used by the AJAX endpoint a lazy card's placeholder fetches from.
     * Returns null (not '') when the key doesn't resolve or the user lacks
     * permission, so the controller can tell "empty widget" apart from
     * "not allowed to see this at all".
     */
    public function renderCard(string $key, WidgetContext $context): ?string
    {
        $entry = $this->registry->find($key);

        if (!$entry || !in_array(WidgetSurface::Admin, $entry['meta']->surfaces, true)) {
            return null;
        }

        if (!WidgetPermissionChecker::check($entry['meta'], $context->user)) {
            return null;
        }

        return $this->renderOne($entry, $context);
    }

    /**
     * @param array{class: string, meta: Widget} $entry
     */
    private function renderOne(array $entry, WidgetContext $context): string
    {
        return $this->outputCache->remember(
            $entry['meta'],
            'admin:' . ($context->locale ?? 'default'),
            function () use ($entry, $context) {
                $instance = app($entry['class']);
                $config = [];

                if ($instance instanceof RendersHtml) {
                    return $instance->render($config, $context);
                }

                if ($instance instanceof ProvidesMetric) {
                    return $this->renderMetricCard($instance->toMetric($config, $context));
                }

                return '';
            },
        );
    }

    private function renderPlaceholder(Widget $meta): string
    {
        $view = 'nexus::' . config('nexus.template') . '.templates.widgets.lazy_placeholder';

        if (!view()->exists($view)) {
            return '';
        }

        return view($view, ['meta' => $meta])->render();
    }

    /**
     * Built-in fallback presentation for a metric-only widget (no
     * RendersHtml of its own) — sparkline/badge polish is Phase 5.4, this is
     * intentionally the bare label+value card.
     */
    private function renderMetricCard(MetricDto $metric): string
    {
        $view = 'nexus::' . config('nexus.template') . '.templates.widgets.metric_card';

        if (!view()->exists($view)) {
            return '';
        }

        return view($view, ['metric' => $metric])->render();
    }
}
