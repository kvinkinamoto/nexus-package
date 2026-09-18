<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Registers a plugin method as a filter callback for the named hook —
 * auto-wired by PluginManager on every class it discovers under
 * app/Nexus/Plugins/*. The method receives the current value (plus
 * whatever extra args the hook call site passes) and must return the
 * (possibly transformed) value.
 *
 * Example:
 * #[Filter(hook: 'shopProduct.price', priority: 10)]
 * public function applyLoyaltyDiscount(float $price, ShopProduct $product): float { ... }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Filter
{
    public function __construct(
        public readonly string $hook,

        /** Lower runs first. Matches WordPress's apply_filters() default of 10. */
        public readonly int $priority = 10,
    ) {}
}
