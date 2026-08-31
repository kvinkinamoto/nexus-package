<?php

namespace Nodex\Nexus\Attributes;

use Attribute;
use Nodex\Nexus\Enums\WidgetSurface;

/**
 * Marks a class as a Nexus widget and declares its metadata. The attribute
 * is authoritative for metadata and which surfaces a widget targets;
 * Contracts\Widgets\WidgetInterface (+ optional RendersHtml/ProvidesMetric/
 * ApiSerializable) is authoritative for what it can actually do —
 * WidgetRegistry cross-checks the two and logs a mismatch (e.g. surfaces
 * including Front without implementing RendersHtml) instead of failing.
 *
 * Example:
 *   #[Widget(name: 'ordersToday', label: 'Orders Today', surfaces: [WidgetSurface::Admin, WidgetSurface::Api], group: 'sales')]
 *   class OrdersTodayWidget implements WidgetInterface, ProvidesMetric { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Widget
{
    public function __construct(
        /** Stable slug, persisted in widget instances instead of the FQCN so renames don't orphan placements. */
        public readonly string $name,

        public readonly string $label,

        /** @var WidgetSurface[] */
        public readonly array $surfaces = [WidgetSurface::Admin],

        public readonly ?string $icon = null,

        /** Grouping key for the widget picker UI. */
        public readonly ?string $group = null,

        /** Owning module name, if any — shown in the widget picker, not a functional dependency. */
        public readonly ?string $module = null,

        /** Seconds to cache getData() output for. Null/0 = no caching. */
        public readonly ?int $cacheTtl = null,

        /**
         * Module name(s) this widget needs enabled to function. Checked via
         * ModuleDependencyChecker — the registry skips (not fails) a widget
         * whose requirement isn't met.
         *
         * @var string[]
         */
        public readonly array $requires = [],

        /** Whether this widget may be listed/served on the Api surface even for anonymous requests. */
        public readonly bool $apiPublic = false,

        /** Default grid size hint for the dashboard layout, e.g. '1x1', '2x1'. */
        public readonly ?string $defaultSize = null,

        /** Permission name required to place/view this widget in the admin dashboard. */
        public readonly ?string $permission = null,

        /**
         * When true, the admin dashboard renders a placeholder for this
         * widget and fetches its HTML via AJAX instead of computing it
         * inline with the rest of the page — for widgets whose getData()
         * is too slow to hold up the dashboard response.
         */
        public readonly bool $lazy = false,
    ) {}
}
