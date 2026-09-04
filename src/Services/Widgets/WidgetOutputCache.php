<?php

namespace Nodex\Nexus\Services\Widgets;

use Illuminate\Support\Facades\Cache;
use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Events\WidgetOutputResolving;

/**
 * Backs #[Widget(cacheTtl:)] — a widget without cacheTtl set behaves exactly
 * as before (callback runs every time, nothing touches the cache store).
 * Each call site builds its own $variantKey from whatever makes its output
 * differ (instance id, query params, locale, ...) so cached entries never
 * leak across contexts that would otherwise produce different output.
 *
 * Also the single, centralized point for the widget.{kind}.{key} plugin
 * filter — every consumer (WidgetApiController, AdminDashboardRenderer,
 * WidgetInstance::render()) already funnels through remember(), so hooking
 * it here once covers all three instead of duplicating the nexus_filter()
 * call at each of them. $kind distinguishes the two genuinely different
 * output shapes a widget can produce: 'data' (a structured array — getData()/
 * ApiSerializable::toApiPayload()'s output, from WidgetApiController) vs
 * 'html' (a rendered string — RendersHtml::render()/the metric-card
 * fallback, from AdminDashboardRenderer and WidgetInstance::render()) — a
 * plugin filtering one should never have to guess which shape it received.
 * Applied before caching, so a filter's mutation is itself what gets cached
 * (consistent output on a cache hit, not just the first cache-cold request).
 */
class WidgetOutputCache
{
    public function remember(Widget $meta, string $variantKey, \Closure $callback, string $kind, WidgetContext $context): mixed
    {
        $produce = function () use ($callback, $meta, $kind, $context) {
            $output = $callback();
            event(new WidgetOutputResolving($meta->name, $kind, $output, $context));

            return nexus_filter("widget.{$kind}.{$meta->name}", $output, $context);
        };

        if (!$meta->cacheTtl) {
            return $produce();
        }

        $key = "nexus:widget:{$meta->name}:{$variantKey}";

        return Cache::remember($key, $meta->cacheTtl, $produce);
    }
}
