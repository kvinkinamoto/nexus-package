<?php

namespace Nodex\Nexus\Services\Widgets;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Nodex\Nexus\Attributes\Widget;
use Nodex\Nexus\Contracts\Widgets\ProvidesMetric;
use Nodex\Nexus\Contracts\Widgets\RendersHtml;
use Nodex\Nexus\Contracts\Widgets\WidgetInterface;
use Nodex\Nexus\Enums\WidgetSurface;
use Nodex\Nexus\Services\ModuleRegistry;

/**
 * Central store of every discovered widget, keyed by its #[Widget(name:)]
 * slug (not the FQCN — see Attributes/Widget.php's docblock on why renames
 * shouldn't orphan placements). NexusServiceProvider::loadWidgets() is what
 * actually walks the filesystem/manifest cache and calls registerFromDiscovery()
 * for each candidate; this class only validates and stores.
 */
class WidgetRegistry
{
    /** @var array<string, array{class: string, meta: Widget}> */
    private array $widgets = [];

    public function __construct(private ModuleRegistry $moduleRegistry)
    {
    }

    public function register(string $class, Widget $meta): void
    {
        $this->widgets[$meta->name] = ['class' => $class, 'meta' => $meta];
    }

    /**
     * Registers a widget from the scalar-array shape produced by
     * ModuleManifestCache::discoverWidgets() — the same shape whether it came
     * from a live filesystem scan or the manifest cache, so the caller never
     * needs to know which. Invalid or currently-unusable widgets are skipped
     * (optionally logged) rather than thrown — one broken widget must never
     * take the whole admin panel down.
     */
    public function registerFromDiscovery(array $entry): void
    {
        $class = $entry['class'];

        if (!is_subclass_of($class, WidgetInterface::class)) {
            Log::warning("Nexus: widget [{$class}] carries #[Widget] but does not implement WidgetInterface — skipped.");
            return;
        }

        if ($this->hasUnmetRequirements($entry['requires'] ?? [])) {
            return;
        }

        $surfaces = array_map(
            fn (string $value) => WidgetSurface::from($value),
            $entry['surfaces'] ?? [],
        );

        foreach ($surfaces as $surface) {
            if (!$this->hasCapabilityFor($class, $surface)) {
                Log::warning("Nexus: widget [{$class}] declares surface [{$surface->value}] but implements neither RendersHtml nor ProvidesMetric.");
            }
        }

        $meta = new Widget(
            name: $entry['name'],
            label: $entry['label'],
            surfaces: $surfaces,
            icon: $entry['icon'] ?? null,
            group: $entry['group'] ?? null,
            module: $entry['module'] ?? null,
            cacheTtl: $entry['cacheTtl'] ?? null,
            requires: $entry['requires'] ?? [],
            apiPublic: $entry['apiPublic'] ?? false,
            defaultSize: $entry['defaultSize'] ?? null,
            permission: $entry['permission'] ?? null,
            lazy: $entry['lazy'] ?? false,
        );

        $this->register($class, $meta);
    }

    private function hasUnmetRequirements(array $requires): bool
    {
        if (empty($requires)) {
            return false;
        }

        $enabledNames = $this->moduleRegistry->getEnabledModules()
            ->pluck('name')
            ->map(fn ($name) => Str::lower($name))
            ->all();

        foreach ($requires as $required) {
            if (!in_array(Str::lower($required), $enabledNames, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Front/Admin need something that can produce HTML or a metric card;
     * Api only needs WidgetInterface::getData(), already guaranteed above.
     */
    private function hasCapabilityFor(string $class, WidgetSurface $surface): bool
    {
        return match ($surface) {
            WidgetSurface::Front, WidgetSurface::Admin => is_subclass_of($class, RendersHtml::class) || is_subclass_of($class, ProvidesMetric::class),
            WidgetSurface::Api => true,
        };
    }

    /**
     * @return array<string, array{class: string, meta: Widget}>
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * @return array<string, array{class: string, meta: Widget}>
     */
    public function forSurface(WidgetSurface $surface): array
    {
        return array_filter(
            $this->widgets,
            fn (array $entry) => in_array($surface, $entry['meta']->surfaces, true),
        );
    }

    /**
     * @return array{class: string, meta: Widget}|null
     */
    public function find(string $key): ?array
    {
        return $this->widgets[$key] ?? null;
    }
}
