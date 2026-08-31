<?php

namespace Nodex\Nexus\Services;

/**
 * A named, priority-ordered extension point any module or plugin can tap
 * into without editing the code that defines it — the same role
 * WordPress's apply_filters()/do_action() play for its plugin ecosystem.
 *
 * Filters transform and return a value (price calculation, validation
 * rules, rendered snippets, ...). Actions run side effects and return
 * nothing (react to a lifecycle event without owning it).
 *
 * This is deliberately independent of PluginManager/#[TargetModule], which
 * only ever mutates one module's admin-panel config DTO at config-build
 * time. Hooks are general purpose and can be called from anywhere in a
 * module's own code.
 *
 * Usage:
 *   nexus_filter('shopProduct.price', $price, $product);
 *   nexus_action('order.created', $order);
 *
 * Registration (usually via #[Filter]/#[Action] on a plugin class method,
 * auto-wired by PluginManager — see Attributes/Filter.php, Attributes/Action.php):
 *   app(HookManager::class)->addFilter('shopProduct.price', $callback, priority: 10);
 */
class HookManager
{
    private array $filters = [];

    private array $actions = [];

    public function addFilter(string $hook, callable|array $callback, int $priority = 10): void
    {
        $this->filters[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }

    public function addAction(string $hook, callable|array $callback, int $priority = 10): void
    {
        $this->actions[$hook][] = ['callback' => $callback, 'priority' => $priority];
    }

    /**
     * Run every registered filter for $hook in priority order, each
     * receiving the current value (plus $args) and returning the next
     * value. Returns $value unchanged if nothing is registered.
     */
    public function filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->sorted($this->filters[$hook] ?? []) as $callback) {
            $value = $this->invoke($callback, [$value, ...$args]);
        }

        return $value;
    }

    /**
     * Run every registered action for $hook in priority order. No return
     * value — for side effects only.
     */
    public function action(string $hook, mixed ...$args): void
    {
        foreach ($this->sorted($this->actions[$hook] ?? []) as $callback) {
            $this->invoke($callback, $args);
        }
    }

    public function hasFilters(string $hook): bool
    {
        return !empty($this->filters[$hook]);
    }

    public function hasActions(string $hook): bool
    {
        return !empty($this->actions[$hook]);
    }

    private function sorted(array $entries): array
    {
        usort($entries, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        return array_column($entries, 'callback');
    }

    /**
     * [ClassNameString, 'method'] is resolved through the container lazily,
     * at call time — a plugin class is never instantiated unless one of
     * its hooks actually fires.
     */
    private function invoke(callable|array $callback, array $args): mixed
    {
        if (is_array($callback) && is_string($callback[0]) && class_exists($callback[0])) {
            $callback = [app($callback[0]), $callback[1]];
        }

        return call_user_func_array($callback, $args);
    }
}
