<?php

namespace Nodex\Nexus\Livewire\Concerns;

use Nodex\Nexus\Enums\RelationConfigParamsEnum;
use Nodex\Nexus\Services\RelationService;

/**
 * ModuleForm's ajax relation-picker: server-side search plus select/remove/
 * clear for belongsTo and belongsToMany/hasMany fields. Extracted verbatim
 * from ModuleForm (no behavior change).
 */
trait ManagesRelationPicker
{
    /**
     * Livewire's magic array-property hook (public array $relationSearchQuery,
     * updating key $fieldName fires updatedRelationSearchQuery($value, $fieldName)).
     * Runs the search server-side via RelationService — the same service the
     * legacy ajax_relation endpoint (AjaxController::relationSearch) calls —
     * so no separate HTTP round trip and no Choices.js are needed here.
     * For a belongsToMany/hasMany field, results already on the tag list are
     * filtered out — the legacy Choices.js UI does the same (relation.blade.php's
     * `existingValues` filter) so a re-search can't offer a duplicate pick.
     */
    public function updatedRelationSearchQuery(string $value, string $fieldName): void
    {
        $moduleConfig = $this->resolveModuleConfig();
        $relationConfig = $moduleConfig->relations->is_available[$fieldName] ?? null;
        if (! $relationConfig) {
            return;
        }

        $relatedModel = (new $moduleConfig->model)->{$fieldName}()->getRelated();

        $selected = array_map('strval', (array) ($this->data[$fieldName] ?? []));

        $this->relationSearchResults[$fieldName] = app(RelationService::class)
            ->search($relatedModel, $relationConfig, $value !== '' ? $value : null)
            ->reject(fn ($item) => in_array((string) $item->id, $selected, true))
            ->map(fn ($item) => ['id' => $item->id, 'label' => $item->label])
            ->values()
            ->all();
    }

    public function selectRelation(string $fieldName, int|string $id, string $label): void
    {
        $moduleConfig = $this->resolveModuleConfig();
        $relationConfig = $moduleConfig->relations->is_available[$fieldName] ?? null;

        if ($relationConfig && $this->isMultipleRelation($relationConfig)) {
            $current = (array) ($this->data[$fieldName] ?? []);
            if (! in_array((string) $id, array_map('strval', $current), true)) {
                $current[] = $id;
            }
            $this->data[$fieldName] = $current;
            $this->relationLabels[$fieldName][$id] = $label;
        } else {
            $this->data[$fieldName] = $id;
            $this->relationLabels[$fieldName] = $label;
        }

        $this->relationSearchResults[$fieldName] = [];
        $this->relationSearchQuery[$fieldName] = '';
    }

    public function removeRelationItem(string $fieldName, int|string $id): void
    {
        $this->data[$fieldName] = array_values(array_filter(
            (array) ($this->data[$fieldName] ?? []),
            fn ($value) => (string) $value !== (string) $id
        ));
        unset($this->relationLabels[$fieldName][$id]);
    }

    public function clearRelation(string $fieldName): void
    {
        $this->data[$fieldName] = null;
        $this->relationLabels[$fieldName] = null;
    }

    private function isMultipleRelation(object $relationConfig): bool
    {
        return in_array($relationConfig->type, [
            RelationConfigParamsEnum::BELONGS_TO_MANY->value,
            RelationConfigParamsEnum::HAS_MANY->value,
        ], true);
    }
}
