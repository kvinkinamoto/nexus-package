<?php

namespace Nodex\Nexus\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nodex\Nexus\Dto\ModuleDtos\RelatedEntityOptionDto;

/**
 * Загальний сервіс для побудови списку сутностей (модулів) для поля типу "пов'язана сутність".
 * Може замінити MenuLinkService::getLinkOptions() та ::getEntities() у будь-якому модулі.
 */
class RelatedEntityFieldService
{
    public const URL_TYPE = 'URL';

    /**
     * Повертає список опцій для <select> вибору типу посилання.
     *
     * @param  array       $allowedModules  Якщо не пустий — показувати лише ці модулі (назви у camelCase).
     * @param  array       $excludedModules Модулі, які завжди виключаються.
     * @param  string|null $urlLabel        Мітка для опції "Пряме посилання / URL". null — відключає опцію URL.
     */
    public function getLinkOptions(
        array $allowedModules = [],
        array $excludedModules = [],
        ?string $morphId = null,
        string $customFieldName,
        ?string $urlLabel = null,
    ): array {
        $defaultExcluded = [
            'modules',
            'moduleSetting',
            'permission',
            'role',
            'activityLog',
            'backup',
            'restore',
            'logViewer',
            'jobViewer',
            'apiDoc',
            'nexusPackageApi',
            'auth',
            'translations',
            'images',
        ];

        $excluded = array_merge($defaultExcluded, $excludedModules);

        $options = [];

//        if ($urlLabel !== null) {
//            $options[] = new RelatedEntityOptionDto('url', self::URL_TYPE, $urlLabel);
//        }

        foreach (ModuleManager::getEnabledModules() as $module) {
//            $moduleName = Str::lower($module->name);

            if (in_array($module->name, $excluded, true)) {
                continue;
            }

            if (!empty($allowedModules) && !in_array($module->name, $allowedModules, true)) {
                continue;
            }

            $config     = ModuleManager::getModuleConfig($module->name);
            $modelClass = $config->model ?? null;

            if (!$modelClass || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            $label = $config->menu->label ?? ucfirst($module->name);
            if (is_string($label) && str_contains($label, '::')) {
                $label = __($label);
            }

            $options[] = new RelatedEntityOptionDto(
                $module->name,
                $modelClass,
                $morphId,
                is_string($label) ? $label : ucfirst($module->name),
                $customFieldName,
            );
        }

        return $options;
    }

    /**
     * Повертає список записів моделі у форматі [id => label] для підвантаження в <select>.
     */
    public function getEntities(string $modelClass, ?string $labelField = null): array
    {
        if ($modelClass === self::URL_TYPE || !class_exists($modelClass)) {
            return [];
        }

        /** @var Model $model */
        $model = app($modelClass);
        $query = $model->newQuery();

        if (method_exists($model, 'scopeIsPublished')) {
            $query->isPublished();
        } elseif (in_array('is_published', $model->getFillable(), true)) {
            $query->where('is_published', 1);
        }

        if (!$labelField) {
            if (property_exists($model, 'translatable') && in_array('title', $model->translatable ?? [], true)) {
                $labelField = 'title';
            } elseif (in_array('name', $model->getFillable(), true)) {
                $labelField = 'name';
            } elseif (in_array('title', $model->getFillable(), true)) {
                $labelField = 'title';
            } else {
                $labelField = 'id';
            }
        }

        return $query->pluck($labelField, 'id')
            ->map(fn ($label) => is_array($label)
                ? ($label[app()->getLocale()] ?? reset($label))
                : $label)
            ->toArray();
    }

}
