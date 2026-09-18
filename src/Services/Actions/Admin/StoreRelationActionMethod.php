<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\RelationConfigParamsEnum;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Services\Abstracted\ModuleManagerAbstract;
use Kalnoy\Nestedset\NodeTrait;

class StoreRelationActionMethod
{
    public static function handle(Model $model, DefaultModuleConfigurationDto $moduleConfig, array $validatedData): void
    {
        DB::transaction(function () use ($model, $moduleConfig, $validatedData) {
            //        dd($model, $moduleConfig, $validatedData);
            $oldData = $model->attributesToArray();
            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'before',
                methodName: 'storeRelation',
                model: $model,
                newData: $validatedData,
                oldData: $oldData
            );
            $relationConfig = null;
            foreach ($validatedData['relation'] ?? [] as $relation => $value) {
                $relationConfig = $moduleConfig->relations->is_available[$relation];
                //            dd($moduleConfig);
                switch ($relationConfig->type) {
                    case RelationConfigParamsEnum::BELONGS_TO_MANY->value:
                        self::saveMultipleRelation($model, $value, $relation);
                        break;
                    case RelationConfigParamsEnum::HAS_MANY->value:
                        self::saveMultipleRelation($model, $value, $relation);
                        break;
                    case RelationConfigParamsEnum::BELONGS_TO->value:
                        self::saveSingleRelation($model, $value, $relation);
                        break;
                    case RelationConfigParamsEnum::HAS_ONE->value:
                        self::saveSingleRelation($model, $value, $relation);
                        break;
                    case RelationConfigParamsEnum::CUSTOM->value:
                        self::saveCustomRelation($model, $value, $relation, $moduleConfig);
                        break;
                }
            }
            CallModuleHookAction::hook(
                moduleConfig: $moduleConfig,
                callPosition: 'after',
                methodName: 'storeRelation',
                model: $model,
                newData: $validatedData,
                oldData: $oldData
            );
        });
    }

    private static function saveCustomRelation(Model $model, array $data, string $relationName, DefaultModuleConfigurationDto $relationConfigDto)
    {
        /**
         * @var $moduleManager ModuleManagerAbstract
         */
        $moduleManager = $relationConfigDto->getModuleManager();
        $moduleManager->storeCustomRelation($relationName, $data, $model);

    }

    private static function saveMultipleRelation(Model $model, array $data, string $relationName)
    {
        $relation = $model->{$relationName}();
        $relatedModel = $relation->getRelated();

        if (!empty($data)) {
            $foreignKey = self::getRelationForeignKey($relation);

            if ($relation instanceof HasOneOrMany) {
                $incoming = collect($data)
                    ->filter(fn($row) => is_array($row))
                    ->map(fn($row) => array_map(
                        fn($v) => is_string($v) ? trim($v) : $v,
                        $row
                    ))
                    ->filter(function (array $row) {
                        $payload = Arr::except($row, ['id']);

                        foreach ($payload as $value) {
                            if (!is_null($value) && $value !== '') {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->values();

                $data = $incoming->toArray();

                // Витягуємо id з data, якщо є
                $ids = array_map(fn($item) => $item['id'] ?? null, $data);
                $ids = array_filter($ids); // видаляємо null

                // Видаляємо записи, яких більше немає у $data
                if (!empty($ids)) {
                    $relation->whereNotIn('id', $ids)->delete();
                } else {
                    $relation->delete();
                }

                //Обробляємо кожен елемент data
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        // Існуючий запис – оновлюємо
                        $related = $relation->getRelated()->find($item['id']);
                        if ($related) {
                            foreach ($item as $key => $value) {
                                if ($key !== 'id') {
                                    $related->{$key} = $value;
                                }
                            }
                            $related->{$foreignKey} = $model->id;
                            $related->save();
                        }
                    } else {
                        // Новий запис – створюємо
                        $item = array_merge(self::extraRelationScopeAttributes($relation), $item);
                        $item[$foreignKey] = $model->id;
                        $relation->create($item);
                    }
                }
            } elseif ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
                $relation->sync($data);
            }
        } elseif ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            $relation->detach();
        } elseif ($relation instanceof HasOneOrMany) {
            $relation->delete();
        }

        self::fixNestedTreeIfNeeded($relatedModel);
    }

    /**
     * A HasMany/MorphMany relation method can carry its own extra ->where()
     * scope beyond the plain foreign key — e.g. ShopProduct's images()
     * (morphMany(...)->where('field_name', 'images')), which is how a single
     * model can expose several distinct morphMany relations (one per
     * gallery-style field) against the same polymorphic table. Relation::
     * create() only ever auto-fills the foreign key/morph-type columns, not
     * arbitrary query-time where() constraints, so a bare $relation->create($item)
     * on such a relation would insert a row that doesn't actually belong to
     * this relation's own scope (field_name left null) — silently invisible
     * to the very query that's supposed to find it again. This reads the
     * simple equality wheres already bound onto the relation's query and
     * carries them into the new row's attributes, so create() produces a row
     * that satisfies its own relation's scope.
     *
     * @return array<string, mixed>
     */
    private static function extraRelationScopeAttributes(HasOneOrMany $relation): array
    {
        $wheres = $relation->getQuery()->getQuery()->wheres ?? [];
        $extra = [];

        foreach ($wheres as $where) {
            if (($where['type'] ?? null) !== 'Basic' || ($where['operator'] ?? null) !== '=' || !isset($where['column'], $where['value'])) {
                continue;
            }

            $column = $where['column'];
            $column = str_contains($column, '.') ? substr($column, strrpos($column, '.') + 1) : $column;
            $extra[$column] = $where['value'];
        }

        return $extra;
    }

    private static function saveSingleRelation(Model $model, ?string $id, string $relationName)
    {
        if ($id) {
            $model->{$relationName}()->associate($id)->save();
        } else {
            $model->{$relationName}()->dissociate()->save();
        }

    }

    private static function fixNestedTreeIfNeeded(Model $model): void
    {
        if (!in_array(NodeTrait::class, class_uses_recursive($model))) {
            return;
        }

        $model->newQuery()->fixTree();
    }

    private static function getRelationForeignKey($relation)
    {
        if ($relation instanceof BelongsToMany || $relation instanceof MorphToMany) {
            return $relation->getForeignPivotKeyName();
        }

        if (method_exists($relation, 'getForeignKeyName')) {
            return $relation->getForeignKeyName();
        }

        throw new \Exception('Unknown relation type: ' . get_class($relation));
    }
}
