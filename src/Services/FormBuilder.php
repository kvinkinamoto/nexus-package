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
        nexus_action('nexus.form.building', $moduleConfiguration, $moduleConfiguration->name, $model instanceof Model ? $model : null);

        $fields = $moduleConfiguration->form->fields ?? [];

        // Legacy event for plugins that still rely on the old string-based dispatch
        Event::dispatch('nexus.form.fields.building', [$moduleConfiguration->name, &$fields]);

        event(new \Nodex\Nexus\Events\FormFieldsPrepared($moduleConfiguration->name, $fields));
        $fields = nexus_filter('nexus.form.fields_prepared', $fields, $moduleConfiguration->name);

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
        /**
         * Module::$name is stored as whatever module-folder name
         * nexus:module:install was given (Str::studly, e.g. "Language" —
         * see ModuleManager::instanceInstall()), not the #[Module(name:)]
         * attribute's lowercase registry key. An exact-match where('name',
         * 'language') therefore never matches a really-installed Language
         * module — use the same case-insensitive lookup Module::findByName()
         * already provides for this exact reason (see its own docblock).
         */
        $languagesModule = Module::findByName('language');

        if (!$languagesModule || !$languagesModule->is_enabled) {
            return [config('app.locale')];
        }

        $model = new ($languagesModule->config->model)();

        return $model::withoutTrashed()
            ->isPublished()
            ->pluck('code');
    }
}
