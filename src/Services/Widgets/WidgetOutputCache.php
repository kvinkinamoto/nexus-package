<?php

namespace Nodex\Nexus\Services\Widgets;

use Illuminate\Support\Facades\Cache;
use Nodex\Nexus\Attributes\Widget;

/**
 * Backs #[Widget(cacheTtl:)] — a widget without cacheTtl set behaves exactly
 * as before (callback runs every time, nothing touches the cache store).
 * Each call site builds its own $variantKey from whatever makes its output
 * differ (instance id, query params, locale, ...) so cached entries never
 * leak across contexts that would otherwise produce different output.
 */
class WidgetOutputCache
{
    public function remember(Widget $meta, string $variantKey, \Closure $callback): mixed
    {
        if (!$meta->cacheTtl) {
            return $callback();
        }

        $key = "nexus:widget:{$meta->name}:{$variantKey}";

        return Cache::remember($key, $meta->cacheTtl, $callback);
    }
}
