<?php

namespace Nodex\Nexus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nodex\Nexus\Contracts\Widgets\RendersHtml;
use Nodex\Nexus\Contracts\Widgets\WidgetInterface;
use Nodex\Nexus\Dto\Widgets\WidgetContext;
use Nodex\Nexus\Enums\WidgetSurface;
use Nodex\Nexus\Services\Widgets\WidgetOutputCache;
use Nodex\Nexus\Services\Widgets\WidgetRegistry;

/**
 * A placed, configured instance of a widget class — the front-placement
 * counterpart to the admin dashboard's DashboardLayoutResolver, except this
 * is persisted per-instance (not per-user) and located via widget_assignments
 * rather than a single ordered key list. #[Field]/#[Column] attributes for
 * the admin management screen land in Phase 5.3's second step, not here —
 * this class is deliberately just the front-rendering half.
 */
class WidgetInstance extends Model
{
    use SoftDeletes;

    protected $table = 'widget_instances';

    protected $fillable = [
        'name',
        'widget_key',
        'widget_class',
        'widget_view',
        'config',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'config' => 'array',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(WidgetAssignment::class, 'widget_instance_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * widget_key (resolved via WidgetRegistry) wins; widget_class is only
     * consulted as a fallback, and only when it independently satisfies
     * WidgetInterface — an unresolvable key must never fall through to an
     * arbitrary stored string being treated as a trusted class name.
     */
    private function resolveWidgetClass(WidgetRegistry $registry): ?string
    {
        if ($this->widget_key) {
            $entry = $registry->find($this->widget_key);
            if ($entry) {
                return $entry['class'];
            }
        }

        if ($this->widget_class && is_subclass_of($this->widget_class, WidgetInterface::class)) {
            return $this->widget_class;
        }

        return null;
    }

    public function render(): string
    {
        $registry = app(WidgetRegistry::class);
        $class = $this->resolveWidgetClass($registry);

        if (!$class || !is_subclass_of($class, RendersHtml::class)) {
            return '';
        }

        $context = new WidgetContext(
            surface: WidgetSurface::Front,
            instanceId: $this->id,
            params: $this->widget_view ? ['view' => $this->widget_view] : [],
        );

        $entry = $this->widget_key ? $registry->find($this->widget_key) : null;

        if (!$entry) {
            return app($class)->render((array) ($this->config ?? []), $context);
        }

        return app(WidgetOutputCache::class)->remember(
            $entry['meta'],
            "front:{$this->id}:" . ($this->widget_view ?? 'default') . ':' . app()->getLocale(),
            fn () => app($class)->render((array) ($this->config ?? []), $context),
            'html',
            $context,
        );
    }
}
