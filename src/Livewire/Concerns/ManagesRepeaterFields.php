<?php

namespace Nodex\Nexus\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;

/**
 * ModuleForm's #[RepeaterField] row CRUD — hydrating existing rows from the
 * model, and the add/remove row actions the repeater's Alpine template wires
 * up client-side. Extracted verbatim from ModuleForm (no behavior change);
 * see that class's own docblock for the repeater pilot's scope.
 */
trait ManagesRepeaterFields
{
    /**
     * '_rowKey' gives each row a stable identity independent of its current
     * array position, for the view's wire:key. Without it, removing row 0
     * shifts row 1 into index 0 — Livewire matches the surviving <tr> by its
     * now-identical positional wire:key and morphs it in place instead of
     * recreating it, so an Alpine cell's local JS state (e.g.
     * cartProductSelect's selectedLabel) stays stale from the row that used
     * to occupy that slot, since Alpine only evaluates x-data once per DOM
     * node, not on every Livewire re-render. Stripped back out before the
     * row reaches StoreRelationActionMethod (see buildLegacyInput()) since
     * it isn't a real column.
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateRepeaterRows(Model $model, FieldConfigDto $field): array
    {
        $rows = [];

        foreach ($model->{$field->name} as $related) {
            $row = ['id' => $related->getKey(), '_rowKey' => 'db-'.$related->getKey()];
            foreach ($field->repeaterColumns as $column) {
                $row[$column->name] = $related->{$column->name};
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Appends one empty row (every declared column defaulting to null) to a
     * repeater field's state — mirrors nexus-repeater.js's client-side
     * template clone, just as reactive array-state instead of cloned DOM.
     */
    public function addRepeaterRow(string $fieldName): void
    {
        $field = $this->resolveModuleConfig()->form->fields[$fieldName] ?? null;
        if (! $field) {
            return;
        }

        $row = ['id' => null, '_rowKey' => 'new-'.(string) Str::uuid()];
        foreach ($field->repeaterColumns as $column) {
            $row[$column->name] = null;
        }

        $this->relationRows[$fieldName][] = $row;
    }

    public function removeRepeaterRow(string $fieldName, int $index): void
    {
        unset($this->relationRows[$fieldName][$index]);
        $this->relationRows[$fieldName] = array_values($this->relationRows[$fieldName] ?? []);
    }
}
