<?php

namespace Nodex\Nexus\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nodex\Nexus\Dto\ModuleDtos\FieldConfigDto;
use Nodex\Nexus\Services\Blocks\BlockTypeRegistry;

/**
 * ModuleForm's #[Field(type: 'blockEditor')] row CRUD — sibling to
 * ManagesRepeaterFields, sharing the same $relationRows state container
 * (rows shaped ['id'=>..,'_rowKey'=>..,'type'=>..,'data'=>[...]]) so
 * buildLegacyInput()/StoreRelationActionMethod::saveMultipleRelation() need
 * no changes to persist them. Unlike a repeater's fixed $field->repeaterColumns,
 * each row's own editable fields come from BlockTypeRegistry::find($row['type'])
 * — see ModuleForm's mount()/buildLegacyInput() for the two small conditions
 * that route a 'blockEditor' field into these methods instead of the
 * repeater ones.
 */
trait ManagesBlockFields
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function hydrateBlockRows(Model $model, FieldConfigDto $field): array
    {
        $registry = app(BlockTypeRegistry::class);
        $rows = [];

        foreach ($model->{$field->name} as $related) {
            $data = $related->data ?? [];
            $type = $registry->find($related->type);

            if ($type) {
                foreach ($type->fields() as $blockField) {
                    if ($blockField->translatable) {
                        $data[$blockField->name] = $this->normalizeTranslatedBlockValue($data[$blockField->name] ?? null);
                    }
                }
            }

            $rows[] = [
                'id' => $related->getKey(),
                '_rowKey' => 'db-'.$related->getKey(),
                'type' => $related->type,
                'data' => $data,
            ];
        }

        return $rows;
    }

    /**
     * A translatable block field's stored value is either already a
     * locale-keyed map (a row saved since this field became translatable)
     * or a legacy plain scalar (saved before). Either way the admin form
     * needs one bound value per active locale — a legacy scalar's value is
     * kept under the current app locale rather than dropped, and every
     * other active locale gets an empty row to fill in.
     *
     * @return array<string, string>
     */
    private function normalizeTranslatedBlockValue(mixed $value): array
    {
        $translations = array_fill_keys($this->activeLocales(), '');

        if (is_array($value)) {
            return array_merge($translations, array_intersect_key($value, $translations));
        }

        if ($value !== null && $value !== '') {
            $translations[app()->getLocale()] = (string) $value;
        }

        return $translations;
    }

    public function addBlock(string $fieldName, string $blockType): void
    {
        $type = app(BlockTypeRegistry::class)->find($blockType);
        if (! $type) {
            return;
        }

        $data = [];
        foreach ($type->fields() as $blockField) {
            $data[$blockField->name] = $blockField->translatable
                ? array_fill_keys($this->activeLocales(), '')
                : null;
        }

        $this->relationRows[$fieldName][] = [
            'id' => null,
            '_rowKey' => 'new-'.(string) Str::uuid(),
            'type' => $blockType,
            'data' => $data,
        ];
    }

    public function removeBlock(string $fieldName, int $index): void
    {
        unset($this->relationRows[$fieldName][$index]);
        $this->relationRows[$fieldName] = array_values($this->relationRows[$fieldName] ?? []);
    }

    public function moveBlockUp(string $fieldName, int $index): void
    {
        $this->swapBlocks($fieldName, $index, $index - 1);
    }

    public function moveBlockDown(string $fieldName, int $index): void
    {
        $this->swapBlocks($fieldName, $index, $index + 1);
    }

    private function swapBlocks(string $fieldName, int $a, int $b): void
    {
        $rows = $this->relationRows[$fieldName] ?? [];
        if (! isset($rows[$a]) || ! isset($rows[$b])) {
            return;
        }

        [$rows[$a], $rows[$b]] = [$rows[$b], $rows[$a]];
        $this->relationRows[$fieldName] = $rows;
    }
}
