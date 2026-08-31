<?php

namespace Nodex\Nexus\Services;

use Illuminate\Database\Eloquent\Model;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Models\Module;

/**
 * Cross-module search, built entirely on top of #[Column(searchable:)] —
 * unlike Filters\FilterHandler's per-module search (which blindly LIKEs
 * every physical column of the one module currently being viewed), this
 * walks every enabled module and only searches columns each module's own
 * ModuleConfiguration explicitly opted in, so it never surfaces internal or
 * irrelevant columns (password hashes, tokens, foreign keys, ...).
 */
class GlobalSearchService
{
    /**
     * @return array<int, array{module: string, label: string, items: array<int, array{id: int|string, label: string, url: string}>}>
     */
    public function search(string $term, int $limitPerModule = 5): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $results = [];

        foreach (Module::query()->where('is_enabled', true)->get() as $module) {
            if (!ModuleManager::checkPermission('index', $module)) {
                continue;
            }

            $config = DefaultModuleConfigurationDto::fromArray($module->config);
            $searchableColumns = array_filter(
                $config->table->columns ?? [],
                fn ($column) => $column->searchable,
            );

            if (empty($searchableColumns)) {
                continue;
            }

            $modelClass = $config->model;

            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            $rows = $modelClass::query()
                ->where(function ($query) use ($searchableColumns, $term) {
                    foreach ($searchableColumns as $column) {
                        $query->orWhere($column->fieldName ?? $column->name, 'LIKE', "%{$term}%");
                    }
                })
                ->limit($limitPerModule)
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $titleColumn = array_key_first($searchableColumns);

            $results[] = [
                'module' => $module->name,
                'label' => $config->menu->label ?? $module->name,
                'items' => $rows->map(fn (Model $row) => [
                    'id' => $row->getKey(),
                    'label' => (string) ($row->{$titleColumn} ?? $row->getKey()),
                    'url' => route('nexus.module.action', ['module' => $module->name, 'action' => 'edit', 'id' => $row->getKey()]),
                ])->all(),
            ];
        }

        return $results;
    }
}
