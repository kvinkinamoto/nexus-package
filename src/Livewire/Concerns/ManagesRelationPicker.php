<?php

namespace Nodex\Nexus\Livewire\Concerns;

use Nodex\Nexus\Enums\AjaxModeEnum;
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
     */
    public function updatedRelationSearchQuery(string $value, string $fieldName): void
    {
        $this->relationSearchResults[$fieldName] = $this->searchRelationOptions($fieldName, $value !== '' ? $value : null);
    }

    /**
     * Preload the (up-to-50) option list immediately rather than leaving it
     * empty until the user types, for every field EXCEPT ones explicitly
     * opted into ajax search-only mode — #[Relation(ajax: true)] with the
     * default 'search' ajaxMode, meant for large tables where preloading
     * everything would be wasteful. That means:
     *  - ajax: false (default)         -> preload (small table, acts like a select)
     *  - ajax: true, ajaxMode: 'search' -> no preload, type to search (large table)
     *  - ajax: true, ajaxMode: 'load'   -> preload AND still searchable
     * Called from ModuleForm::mount() right after a relation field's
     * data/relationLabels are hydrated.
     */
    private function seedRelationOptionsForLoadMode(string $fieldName, object $relationConfig): void
    {
        $isAjax = $relationConfig->ajaxConfig->isAjax ?? false;
        $mode = $relationConfig->ajaxConfig->mode ?? null;

        if ($isAjax && $mode !== AjaxModeEnum::LOAD->value) {
            return;
        }

        $this->relationSearchResults[$fieldName] = $this->searchRelationOptions($fieldName, null);
    }

    /**
     * Shared by the search-as-you-type hook above and the 'load' mode seeding:
     * results already on the tag list are filtered out — the legacy
     * Choices.js UI does the same (relation.blade.php's `existingValues`
     * filter) so a re-search/re-seed can't offer a duplicate pick.
     */
    private function searchRelationOptions(string $fieldName, ?string $queryText): array
    {
        $moduleConfig = $this->resolveModuleConfig();
        $relationConfig = $moduleConfig->relations->is_available[$fieldName] ?? null;
        if (! $relationConfig) {
            return [];
        }

        $relatedModel = (new $moduleConfig->model)->{$fieldName}()->getRelated();

        $selected = array_map('strval', (array) ($this->data[$fieldName] ?? []));

        return app(RelationService::class)
            ->search($relatedModel, $relationConfig, $queryText)
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

        $this->relationSearchQuery[$fieldName] = '';
        $this->relationSearchResults[$fieldName] = [];
        if ($relationConfig) {
            $this->seedRelationOptionsForLoadMode($fieldName, $relationConfig);
        }
    }

    public function removeRelationItem(string $fieldName, int|string $id): void
    {
        $this->data[$fieldName] = array_values(array_filter(
            (array) ($this->data[$fieldName] ?? []),
            fn ($value) => (string) $value !== (string) $id
        ));
        unset($this->relationLabels[$fieldName][$id]);

        $moduleConfig = $this->resolveModuleConfig();
        $relationConfig = $moduleConfig->relations->is_available[$fieldName] ?? null;
        if ($relationConfig) {
            $this->seedRelationOptionsForLoadMode($fieldName, $relationConfig);
        }
    }

    public function clearRelation(string $fieldName): void
    {
        $this->data[$fieldName] = null;
        $this->relationLabels[$fieldName] = null;

        $moduleConfig = $this->resolveModuleConfig();
        $relationConfig = $moduleConfig->relations->is_available[$fieldName] ?? null;
        if ($relationConfig) {
            $this->seedRelationOptionsForLoadMode($fieldName, $relationConfig);
        }
    }

    private function isMultipleRelation(object $relationConfig): bool
    {
        return in_array($relationConfig->type, [
            RelationConfigParamsEnum::BELONGS_TO_MANY->value,
            RelationConfigParamsEnum::HAS_MANY->value,
        ], true);
    }
}
