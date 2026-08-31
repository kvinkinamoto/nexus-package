<?php

namespace Nodex\Nexus\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Kalnoy\Nestedset\NodeTrait;
use ReflectionClass;
use Nodex\Nexus\Core\Contracts\ModuleManagerIntegrateInterface;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Dto\ModuleDtos\RelationsConfigDto;
use Nodex\Nexus\Events\AdminFormBuilding;
use Nodex\Nexus\Models\Module;

class FormBuilder
{
    public static function build(DefaultModuleConfigurationDto $moduleConfiguration, $model = null)
    {
        // ── Dispatch AdminFormBuilding ──────────────────────────────────────────
        // Other modules can listen to this event and inject their own fields,
        // tabs, and sections into any module's form without modifying it directly.
        // Example: the SEO module adds meta_title/meta_description if the model
        // implements HasSeoInterface. No coupling. No editing ShopProduct config.
        event(new AdminFormBuilding(
            moduleName: $moduleConfiguration->name,
            config: $moduleConfiguration,
            model: $model instanceof Model ? $model : null,
        ));

        $fields = $moduleConfiguration->form->fields ?? [];

        // Legacy event for plugins that still rely on the old string-based dispatch
        Event::dispatch('nexus.form.fields.building', [$moduleConfiguration->name, &$fields]);

        event(new \Nodex\Nexus\Events\FormFieldsPrepared($moduleConfiguration->name, $fields));

        $validation = collect($fields)
            ->filter(fn($field) => isset($field->validation))
            ->pluck('validation', 'name')
            ->toArray();

        self::getRelationData($model, $moduleConfiguration->relations);

        return [
            'fields' => $fields,
            'model' => $model,
            'validation' => $validation,
            'relatedData' => self::getRelationships($model, $moduleConfiguration->relations),
            'languages' => self::getLanguages(),
        ];
    }

    public static function getRelationData(?Model $model, RelationsConfigDto $relations)
    {
        if (!$model) {
            return null;
        }
        //        $model->load('roles', 'permissions');
        $relationColumn = $relations->relationColumn ?? [];
        $withRelations = [];
        foreach ($relationColumn as $relation => $column) {
            $withRelations[$relation] = function ($query) use ($column) {
                $query->select('id', $column);
            };
        }
        $model->load($withRelations);
        return $model;
    }

    public static function getRelationships(?Model $model, RelationsConfigDto $relationConfig)
    {
        $relationships = [];

        if (!$model) {
            return [];
        }

        $relationService = app(RelationService::class);

        foreach ($relationConfig->is_available as $methodName => $config) {
            if (!method_exists($model, $methodName)) {
                continue;
            }

            $oldValues = request()->old("relation.{$methodName}");
            if ($oldValues !== null) {
                $oldValues = is_array($oldValues) ? $oldValues : [$oldValues];
            }

            $relationships[$methodName] = $relationService->getInitialData($model, $methodName, $config, $oldValues);
        }

        return $relationships;
    }

    public static function getRelationShopDataToFront(Model $relatedModel, &$query)
    {
        $reflection = new ReflectionClass($relatedModel);
        $traits = $reflection->getTraitNames();
        if (in_array(NodeTrait::class, $traits)) {
            /**
             * @var NodeTrait $relatedModel
             * Only add withDepth() for depth-column rendering.
             * Sorting (including the secondary _lft ordering) is handled by AddSorterActionMethod.
             */
            $query->withDepth();
        }
        return $query;
    }

    public static function useDefaultOrderIfExist(Model $relatedModel, &$query)
    {
        $reflection = new ReflectionClass($relatedModel);
        $traits = $reflection->getTraitNames();
        if (in_array(NodeTrait::class, $traits)) {
            /**
             * @var NodeTrait $relatedModel
             */
            $query->withDepth()
                ->defaultOrder();
        }
        return $query;
    }

    public static function getLanguages()
    {
        $languagesModule = Module::query()
            ->where('name', 'language')
            ->where('is_enabled', true)
            ->first();

        if (!$languagesModule) {
            return [config('app.locale')];
        }

        $model = new ($languagesModule->config->model)();

        return $model::withoutTrashed()
            ->where('is_published', true)
            ->pluck('code');
    }
}
