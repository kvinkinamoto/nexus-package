<?php

namespace Nodex\Nexus\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\RelationConfigDto;
use Nodex\Nexus\Enums\AjaxModeEnum;

class RelationService
{
    /**
     * Get initial data for a relation field in a form.
     */
    public function getInitialData(Model $model, string $relationName, RelationConfigDto $config, ?array $oldValues = null): Collection
    {
        if (!method_exists($model, $relationName)) {
            return collect();
        }

        $relation = $model->$relationName();
        if (!$relation instanceof Relation) {
            return collect();
        }

        $relatedModel = $relation->getRelated();
        $primaryKey = $relatedModel->getKeyName();
        $ajaxConfig = $config->ajaxConfig;
        $isAjax = $ajaxConfig->isAjax ?? false;

        // 1. Get selected IDs from model or old values
        $selectedIds = $this->getSelectedIds($model, $relationName, $oldValues);

        // 2. Fetch data
        if ($isAjax) {
            $mode = $ajaxConfig->mode ?? AjaxModeEnum::SEARCH->value;
            
            if ($mode === AjaxModeEnum::LOAD->value) {
                // Fetch selected + some initial records
                $results = $relatedModel->newQuery()->whereIn($primaryKey, $selectedIds)->get();
                $limit = 50 - $results->count();
                if ($limit > 0) {
                    $extra = $relatedModel->newQuery()
                        ->whereNotIn($primaryKey, $selectedIds)
                        ->limit($limit)
                        ->get();
                    $results = $results->merge($extra);
                }
            } else {
                // Search mode: only fetch selected
                $results = !empty($selectedIds) 
                    ? $relatedModel->newQuery()->whereIn($primaryKey, $selectedIds)->get()
                    : collect();
            }
        } else {
            // Non-AJAX: Load all (basic filtering)
            $showField = $config->showField ?? 'name';
            $query = $relatedModel->newQuery()->select($relatedModel->getKeyName(), $showField);
            
            if (method_exists(FormBuilder::class, 'getRelationShopDataToFront')) {
                FormBuilder::getRelationShopDataToFront($relatedModel, $query);
            }
            $results = $query->get();

        }

        return $this->formatCollection($results, $config);
    }

    /**
     * Search for related records via AJAX.
     */
    public function search(Model $relatedModel, RelationConfigDto $config, ?string $queryText): Collection
    {
        $query = $relatedModel->newQuery();
        $primaryKey = $relatedModel->getKeyName();
        $showField = $config->showField ?? 'name';

        if ($queryText) {
            $qLower = mb_strtolower($queryText);
            $query->where(function ($sub) use ($showField, $primaryKey, $qLower) {
                $sub->where(DB::raw("LOWER({$showField})"), 'LIKE', "%{$qLower}%");
                if (is_numeric($qLower)) {
                    $sub->orWhere($primaryKey, $qLower);
                }
            });
        }

        $results = $query->limit(50)->get();
        return $this->formatCollection($results, $config);
    }

    /**
     * Format a collection of models into a consistent id/label format.
     */
    public function formatCollection(Collection $collection, RelationConfigDto $config): Collection
    {
        $resourceClass = $config->ajaxConfig->resource ?? null;
        if ($resourceClass && class_exists($resourceClass)) {
            return collect($resourceClass::collection($collection)->resolve())->map(fn($item) => (object)$item);
        }

        $showField = $config->showField ?? 'name';
        $primaryKey = $collection->first()?->getKeyName() ?? 'id';

        return $collection->map(function ($item) use ($showField, $primaryKey) {
            $label = $this->formatLabel($item, $showField);
            
            return (object)[
                'id' => $item->$primaryKey,
                'label' => $label,
                $showField => $label
            ];
        });
    }

    /**
     * Robust label extraction logic.
     */
    public function formatLabel(Model $item, string $showField): string
    {
        $val = $item->{$showField};

        if (method_exists($item, 'getTranslation')) {
            // Check if it's translatable
            $isTranslatable = property_exists($item, 'translatable') && is_array($item->translatable) && in_array($showField, $item->translatable);
            if ($isTranslatable) {
                return (string)($item->getTranslation($showField, app()->getLocale(), false) ?: $val);
            }
        }

        // Handle JSON or Array labels
        if (is_string($val) && str_starts_with(trim($val), '{') && ($decoded = json_decode($val, true))) {
            return (string)($decoded[app()->getLocale()] ?? $decoded[config('app.fallback_locale')] ?? current($decoded) ?? $val);
        }

        if (is_array($val)) {
            return (string)($val[app()->getLocale()] ?? $val[config('app.fallback_locale')] ?? current($val) ?? '');
        }

        if (is_object($val) && !method_exists($val, '__toString')) {
            return $item->getKey();
        }

        return (string)($val ?? $item->getKey());

    }

    protected function getSelectedIds(Model $model, string $relationName, ?array $oldValues): array
    {
        $selectedIds = [];
        
        // 1. Start with old values if they exist
        if ($oldValues !== null) {
            // Filter out non-scalar values to avoid "Array to string conversion" in array_unique
            $scalarOldValues = array_filter($oldValues, fn($v) => is_scalar($v));
            return array_values(array_unique($scalarOldValues));
        }


        // 2. Otherwise get from model
        if ($model->exists) {
            $relation = $model->$relationName();
            $results = $relation->getResults();
            $primaryKey = $relation->getRelated()->getKeyName();

            if ($results instanceof \Illuminate\Database\Eloquent\Collection) {
                $selectedIds = $results->pluck($primaryKey)->toArray();
            } elseif ($results instanceof Model) {
                $selectedIds = [$results->$primaryKey];
            }
        }

        return $selectedIds;
    }
}
