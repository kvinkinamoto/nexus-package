<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Registers a plugin method as an action callback for the named hook —
 * auto-wired by PluginManager on every class it discovers under
 * app/Nexus/Plugins/*. Runs for side effects only; no return value.
 *
 * Example:
 * #[Action(hook: 'order.created', priority: 10)]
 * public function notifyWarehouse(Order $order): void { ... }
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Action
{
    public function __construct(
        public readonly string $hook,

        /** Lower runs first. Matches WordPress's do_action() default of 10. */
        public readonly int $priority = 10,
    ) {}
}
