<?php

namespace Nodex\Nexus\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WidgetAssignment extends Model
{
    protected $table = 'widget_assignments';

    protected $fillable = [
        'widget_instance_id',
        'position',
        'target_template',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function widgetInstance(): BelongsTo
    {
        return $this->belongsTo(WidgetInstance::class, 'widget_instance_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * NULL target_template = shown on every template. $targetTemplate carrying
     * a Blade view identifier ("module::path.to.view") gets its namespace
     * segment lowercased before comparing, so a differently-cased namespace
     * (e.g. "siteFrontend::pages.index" vs stored "sitefrontend::pages.index")
     * still matches.
     */
    public function scopeForPosition($query, string $position, string $targetTemplate = 'default')
    {
        if (str_contains($targetTemplate, '::')) {
            [$ns, $path] = explode('::', $targetTemplate, 2);
            $targetTemplate = strtolower($ns) . '::' . $path;
        }

        return $query
            ->where('position', $position)
            ->where('is_active', true)
            ->where(function ($q) use ($targetTemplate) {
                $q->whereNull('target_template')
                    ->orWhere('target_template', $targetTemplate)
                    ->orWhereRaw('LOWER(target_template) = ?', [strtolower($targetTemplate)]);
            });
    }
}
