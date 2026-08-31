<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Enums\AvailableActionEnum;
use Nodex\Nexus\Events\EntityCreated;
use Nodex\Nexus\Events\EntityCreating;
use Nodex\Nexus\Http\Actions\CallModuleHookAction;
use Nodex\Nexus\Http\Actions\GetModuleRequestAction;
use Nodex\Nexus\Services\SchemaColumnsCache;

class DuplicateActionMethod
{
    public static function handle(FormRequest $request, DefaultModuleConfigurationDto $moduleConfig, ?string $id = null): RedirectResponse
    {
        return DB::transaction(function () use ($request, $moduleConfig, $id) {
            $modelClass = $moduleConfig->model;
            /** @var Model $originalModel */
            $originalModel = $modelClass::findOrFail($id);

            $duplicatableRelations = $request->input('duplicatable_relations', []);
            $duplicateTree = $request->boolean('duplicate_tree', false);

            $newModel = self::duplicateModel($originalModel, $moduleConfig, $duplicatableRelations, $duplicateTree);

            $redirectQueryString = $request->input('redirect_query');
            $routeParameters = [
                'module' => $moduleConfig->name,
                'action' => 'index',
            ];

            if ($redirectQueryString) {
                parse_str($redirectQueryString, $parsedQuery);
                $routeParameters = array_merge($parsedQuery, $routeParameters);
            }

            return redirect()->route('nexus.module.action', $routeParameters)
                ->with('success', 'Record duplicated successfully.')
                ->with('alert_message', __('nexus::translate.alert.duplicate_success'))->with('alert_type', 'success');
        });
    }

    public static function duplicateModel(Model $model, DefaultModuleConfigurationDto $moduleConfig, array $duplicatableRelations = [], bool $duplicateTree = false): Model
    {
        /** @var Model $newModel */
        $newModel = $model->replicate();

        // Handle unique fields
        self::handleUniqueFields($newModel, $model, $moduleConfig);

        // Sanitize attributes
        $table = $newModel->getTable();
        $columns = SchemaColumnsCache::get($table);
        $attributes = $newModel->getAttributes();

        foreach ($attributes as $key => $value) {
            if (! in_array($key, $columns)) {
                unset($newModel->$key);
            }
        }

        // Specific logic for NestedSet tree models
        if (self::isTreeModel($model, $moduleConfig)) {
            $parentIdName = method_exists($model, 'getParentIdName') ? $model->getParentIdName() : 'parent_id';

            // Clear indexes to allow NestedSet to recalculate them on save
            if (isset($newModel->_lft)) {
                unset($newModel->_lft);
            }
            if (isset($newModel->_rgt)) {
                unset($newModel->_rgt);
            }

            // Explicitly preserve parent_id if column exists
            if (Schema::hasColumn($table, $parentIdName) && isset($model->$parentIdName)) {
                $newModel->{$parentIdName} = $model->$parentIdName;
            }
        }

        // Get validated data if a custom request exists for duplication
        $moduleRequest = GetModuleRequestAction::getRequestByMethodName(AvailableActionEnum::DUPLICATE_ACTION->value, $moduleConfig);
        $data = $moduleRequest ? $moduleRequest->validated() : [];

        // Trigger events and hooks before saving
        event(new EntityCreating($newModel, $data, $moduleConfig));

        CallModuleHookAction::hook(
            moduleConfig: $moduleConfig,
            callPosition: 'before',
            methodName: 'duplicate',
            model: $newModel,
            newData: $data
        );

        // Fill only the new/modified data
        $newModel->fill(collect($data)->only($newModel->getFillable())->toArray());
        $newModel->save();

        // Trigger events and hooks after saving
        event(new EntityCreated($newModel, $moduleConfig));
        CallModuleHookAction::hook(
            moduleConfig: $moduleConfig,
            callPosition: 'after',
            methodName: 'duplicate',
            model: $newModel,
            newData: $newModel->getAttributes(),
            oldData: $model->getAttributes()
        );

        // Handle relations
        if (! empty($duplicatableRelations)) {
            foreach ($duplicatableRelations as $relationName) {
                if (method_exists($model, $relationName)) {
                    $relation = $model->$relationName();

                    if ($relation instanceof BelongsToMany) {
                        $newModel->$relationName()->attach($model->$relationName->pluck('id')->toArray());
                    } elseif ($relation instanceof HasMany) {
                        foreach ($model->$relationName as $child) {
                            $newChild = self::duplicateModel($child, $moduleConfig, $duplicatableRelations, $duplicateTree);
                            $newChild->{$relation->getForeignKeyName()} = $newModel->id;
                            $newChild->save();
                        }
                    } elseif ($relation instanceof HasOne) {
                        if ($model->$relationName) {
                            $newChild = self::duplicateModel($model->$relationName, $moduleConfig, $duplicatableRelations, $duplicateTree);
                            $newChild->{$relation->getForeignKeyName()} = $newModel->id;
                            $newChild->save();
                        }
                    }
                }
            }
        }

        // Handle tree recursion
        if ($duplicateTree && self::isTreeModel($model, $moduleConfig)) {
            $childrenRelation = method_exists($model, 'children') ? 'children' : null;
            if ($childrenRelation) {
                foreach ($model->$childrenRelation as $child) {
                    $newChild = self::duplicateModel($child, $moduleConfig, $duplicatableRelations, true);
                    $parentIdName = method_exists($newChild, 'getParentIdName') ? $newChild->getParentIdName() : 'parent_id';
                    if (Schema::hasColumn($newChild->getTable(), $parentIdName)) {
                        $newChild->{$parentIdName} = $newModel->id;
                        $newChild->save();
                    }
                }
            }
        }

        return $newModel;
    }

    protected static function handleUniqueFields(Model $newModel, Model $originalModel, DefaultModuleConfigurationDto $moduleConfig)
    {
        $table = $newModel->getTable();
        $fieldsToModify = self::getUniqueColumns($table);

        foreach ($fieldsToModify as $column) {
            if (Schema::hasColumn($table, $column)) {
                $isTranslatable = method_exists($newModel, 'isTranslatableAttribute') && $newModel->isTranslatableAttribute($column);

                if ($isTranslatable && method_exists($newModel, 'getTranslations')) {
                    $translations = $originalModel->getTranslations($column);
                    $newTranslations = [];
                    foreach ($translations as $locale => $localeValue) {
                        $newTranslations[$locale] = self::generateUniqueLocaleValue($newModel, $column, $locale, $localeValue);
                    }
                    $newModel->setTranslations($column, $newTranslations);
                } elseif (isset($newModel->$column)) {
                    $originalValue = $newModel->$column;
                    $newModel->$column = self::generateUniquePlainValue($newModel, $column, $originalValue);
                }
            }
        }

        // If there's a 'name' or 'title', append ' (Copy)' if not already modified
        if (! in_array('name', $fieldsToModify) && isset($newModel->name)) {
            $newModel->name = $newModel->name.' (Copy)';
        } elseif (! in_array('title', $fieldsToModify) && isset($newModel->title)) {
            $newModel->title = $newModel->title.' (Copy)';
        }
    }

    protected static function getUniqueColumns(string $table): array
    {
        $uniqueColumns = [];
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if ($index['unique'] && count($index['columns']) === 1) {
                    $uniqueColumns[] = $index['columns'][0];
                }
            }
        } catch (\Exception $e) {

        }

        return $uniqueColumns;
    }

    protected static function generateUniquePlainValue(Model $model, string $column, mixed $value): mixed
    {
        $originalValue = $value;
        $i = 1;

        while ($model::where($column, $value)->exists()) {
            if (is_string($originalValue)) {
                $value = $originalValue.'-'.$i;
            } else {
                $value = $originalValue.$i;
            }
            $i++;
        }

        return $value;
    }

    protected static function generateUniqueLocaleValue(Model $model, string $column, string $locale, mixed $value): mixed
    {
        $originalValue = $value;
        $i = 1;

        while ($model::where("{$column}->{$locale}", $value)->exists()) {
            if (is_string($originalValue)) {
                $value = $originalValue.'-'.$i;
            } else {
                $value = $originalValue.$i;
            }
            $i++;
        }

        return $value;
    }

    public static function isTreeModel(Model $model, DefaultModuleConfigurationDto $moduleConfig): bool
    {
        return $moduleConfig->isTree ?? false;
    }
}
