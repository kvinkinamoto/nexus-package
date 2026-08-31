<?php

namespace Nodex\Nexus\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kalnoy\Nestedset\NodeTrait;
use Nodex\Nexus\Dto\ModuleDtos\DefaultModuleConfigurationDto;
use Nodex\Nexus\Events\AdminTableBuilding;
use Nodex\Nexus\Events\TableDataPrepared;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Services\Actions\Admin\AddFilterActionMethod;
use Nodex\Nexus\Services\Actions\Admin\AddSorterActionMethod;
use Nodex\Nexus\Services\QueryBuilders\UniversalFilterBuilder;

class TableBuilder
{
    public function build(Module $module, Request|array $request, bool $sql = false, ?int $userId = null)
    {
        $userId = $userId ?? auth()->id();
        if (is_array($request)) {
            $request = new Request($request);
        }
        $config = DefaultModuleConfigurationDto::fromArray($module->config);

        if (empty($config)) {
            throw new \Exception("Module configuration for {$module->name} not found");
        }

        // ── Dispatch AdminTableBuilding ─────────────────────────────────────────
        // Other modules can listen to this event and inject columns, filters,
        // or actions into any module's table without modifying it directly.
        event(new AdminTableBuilding(
            moduleName: $module->name,
            config: $config,
        ));

        /**
         * @var Model $modelClass
         */
        $modelClass = $config->model;
        if (! isset($modelClass) || $modelClass == Model::class) {
            return null;
        }
        $query = $modelClass::query();

        // ── Lenses: a named, pre-configured filter/column/sort preset ──────
        // reusing the same condition shape UniversalFilterBuilder already
        // consumes for the ad-hoc "Universal Filters" panel — no second
        // filtering engine needed. See #[TableLens].
        $lenses = $config->lenses ?? [];
        $activeLensName = $request->get('lens');
        $activeLens = ($activeLensName && isset($lenses[$activeLensName])) ? $lenses[$activeLensName] : null;

        // Застосування фільтрів
        $filters = $config->table?->filters ?? null;
        $dynRaw = $request->get('dyn', []);
        $dynFilters = is_array($dynRaw) ? array_values($dynRaw) : [];
        if ($activeLens) {
            $dynFilters = array_merge($activeLens->conditions, $dynFilters);
        }
        $query = AddFilterActionMethod::handle($query, $request->get('filter', []), $module, $userId, $dynFilters);

        $query = FormBuilder::getRelationShopDataToFront(new $modelClass, $query);

        // Пагінація
        $paginationConfig = config('nexus.table.pagination', []);
        $perPageOptions = $paginationConfig['per_page_options'] ?? [];
        $defaultPerPage = $paginationConfig['default_per_page'] ?? 15;

        $requestedPerPage = (int) $request->get('per_page', $defaultPerPage);
        $perPage = in_array($requestedPerPage, $perPageOptions) ? $requestedPerPage : $defaultPerPage;
        $currentPage = (int) $request->get('page', 1);

        // Застосування сортування
        $defaultSortColumn = ($activeLens && $activeLens->sort) ? $activeLens->sort : ($config->table?->defaultSortColumn ?? null);
        $defaultSortDirection = $config->table?->defaultSortDirection ?? 'asc';
        $isTreeModule = $config->isTree ?? false;
        $sortParam = $request->get('sort');

        // Determine effective sort column + direction
        if (! empty($sortParam)) {
            $effectiveSortDirection = str_starts_with($sortParam, '-') ? 'desc' : 'asc';
            $effectiveSortColumn = ltrim($sortParam, '-');
        } else {
            $effectiveSortColumn = $defaultSortColumn;
            $effectiveSortDirection = $defaultSortDirection;
        }

        // For tree modules: PHP-side sibling sort preserves hierarchy
        // while re-sorting children within each parent by the chosen column.
        $usePHPTreeSort = false;
        if ($isTreeModule && $effectiveSortColumn !== null) {
            $isSortable = collect($config->table?->columns ?? [])
                ->first(fn ($c) => $c->name === $effectiveSortColumn && ($c->sortable ?? false));
            if ($isSortable) {
                $usePHPTreeSort = true;
            }
        }

        // Filter visible columns
        $allColumns = collect($config->table?->columns ?? []);
        $tableColumns = $allColumns->all();

        if ($userId) {
            $userPref = DB::table('nexus_user_table_preferences')
                ->where('user_id', $userId)
                ->where('module', $module->name)
                ->first();

            if ($userPref && $userPref->visible_columns) {
                $visibleColumnNames = json_decode($userPref->visible_columns, true) ?? [];
                $tableColumns = $allColumns->filter(fn ($col) => in_array($col->name ?? $col['name'], $visibleColumnNames))->values()->all();
            } else {
                $tableColumns = $allColumns->filter(fn ($col) => $col->tableDefault ?? true)->values()->all();
            }
        } else {
            $tableColumns = $allColumns->filter(fn ($col) => $col->tableDefault ?? true)->values()->all();
        }

        if ($activeLens && $activeLens->columns) {
            $tableColumns = $allColumns
                ->filter(fn ($col) => in_array($col->name ?? $col['name'], $activeLens->columns))
                ->values()->all();
        }

        $filterableFields = $this->getFilterableFields($config, $modelClass);

        if ($sql) {
            return [
                'query' => $query,
                'columns' => $tableColumns,
            ];
        }

        // Lens tab badge counts — each is its own total against the model,
        // deliberately independent of search/dyn filters currently active
        // (same simple "how many records overall" semantics as Nova's
        // default lens count), so this is a handful of cheap COUNT queries,
        // not one per row.
        $lensCounts = [];
        if (! empty($lenses)) {
            $lensCounts['__all__'] = $modelClass::query()->count();
            foreach ($lenses as $lensName => $lens) {
                $lensCounts[$lensName] = UniversalFilterBuilder::apply($modelClass::query(), $lens->conditions)->count();
            }
        }

        if ($usePHPTreeSort) {
            // Fetch ALL records (filters already applied, depth added by getRelationShopDataToFront).
            // Natural tree order from DB so children follow parents in the raw result.
            $modelInstance = new $modelClass;
            if (in_array(NodeTrait::class, class_uses_recursive($modelInstance))) {
                $query->orderBy($modelInstance->getLftName(), 'asc');
            }
            $allRecords = $query->get();

            // Recursively sort siblings at every level
            $sortedRecords = self::sortTreeByColumn($allRecords, $effectiveSortColumn, $effectiveSortDirection, $modelInstance);

            // Manual pagination
            $total = $sortedRecords->count();
            $items = $sortedRecords->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $data = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'query' => request()->all(),
                ]
            );
        } else {
            // Standard SQL sorting
            $query = AddSorterActionMethod::handle(
                $query,
                $config->table?->columns ?? [],
                $sortParam,
                $defaultSortColumn,
                $defaultSortDirection,
                $isTreeModule
            );
            $data = $query->paginate($perPage, page: $currentPage);
        }

        event(new TableDataPrepared($module->name, $data));

        return [
            'data' => $data,
            'columns' => $tableColumns,
            'allColumns' => $allColumns->all(),
            'filters' => $filters,
            'filterableFields' => $filterableFields,
            'actions' => $config->table?->actions ?? null,
            'actionGroup' => $config->table?->actionGroup ?? null,
            'pagination' => [
                'per_page_options' => $perPageOptions,
                'current_per_page' => $perPage,
            ],
            'defaultSortColumn' => $defaultSortColumn,
            'defaultSortDirection' => $defaultSortDirection,
            'lenses' => $lenses,
            'activeLens' => $activeLens ? $activeLensName : null,
            'lensCounts' => $lensCounts,
        ];
    }

    // -------------------------------------------------------------------------
    // Tree PHP-side sort helpers
    // -------------------------------------------------------------------------

    /**
     * Sort a flat collection of nested-set records so that siblings at every
     * level are ordered by $column/$direction, while parent→child nesting is
     * preserved. Returns a flat collection in the correct display order.
     */
    private static function sortTreeByColumn(
        Collection $allRecords,
        string $column,
        string $direction,
        Model $modelInstance
    ): Collection {
        $parentIdName = 'parent_id';
        if (in_array(NodeTrait::class, class_uses_recursive($modelInstance))) {
            $parentIdName = $modelInstance->getParentIdName();
        }

        // Group by parent_id; root records are keyed as '__root__'
        $byParentId = $allRecords->groupBy(fn ($r) => $r->$parentIdName ?? '__root__');

        $result = collect();
        self::flattenSortedTree($byParentId, '__root__', $column, $direction, $result);

        return $result;
    }

    /**
     * Recursively visit every sibling group, sort it, push to $result, then
     * recurse into each node's children.
     */
    private static function flattenSortedTree(
        Collection $byParentId,
        mixed $currentParentKey,
        string $column,
        string $direction,
        Collection &$result
    ): void {
        $siblings = $byParentId->get($currentParentKey, collect());

        $sorted = $direction === 'desc'
            ? $siblings->sortByDesc(fn ($r) => self::getRecordSortValue($r, $column))
            : $siblings->sortBy(fn ($r) => self::getRecordSortValue($r, $column));

        foreach ($sorted as $record) {
            $result->push($record);
            self::flattenSortedTree($byParentId, $record->id, $column, $direction, $result);
        }
    }

    /**
     * Extract a comparable value from a record for the given column.
     * Handles Spatie translatable (JSON) attributes automatically.
     */
    private static function getRecordSortValue(mixed $record, string $column): mixed
    {
        // Spatie HasTranslations: sort by current locale's text
        if (
            method_exists($record, 'getTranslation')
            && ! empty($record->translatable)
            && in_array($column, $record->translatable)
        ) {
            return mb_strtolower(
                $record->getTranslation($column, app()->getLocale(), false) ?? ''
            );
        }

        $value = $record->$column ?? null;

        // Lowercase strings for consistent locale-aware comparison
        return is_string($value) ? mb_strtolower($value) : $value;
    }

    private function getFilterableFields($config, $modelClass)
    {
        $filterableFields = [];
        try {
            $instance = new $modelClass;
            $tableName = $instance->getTable();

            // Check if explicitly configured
            if (! empty($config->table?->universalFilters)) {
                foreach ($config->table->universalFilters as $key => $fields) {
                    if ($key === 'Main Table' || $key === $tableName || $key === 'main') {
                        $groupName = 'Main Table';
                        $filterableFields[$groupName] = [];
                        foreach ((array) $fields as $col) {
                            $filterableFields[$groupName][$col] = ['label' => $col, 'isRelation' => false];
                        }
                    } else {
                        $groupName = $key;
                        $filterableFields[$groupName] = [];
                        foreach ((array) $fields as $col) {
                            $filterableFields[$groupName][$key.'.'.$col] = [
                                'label' => $col,
                                'isRelation' => true,
                                'relationName' => $key,
                            ];
                        }
                    }
                }

                return $filterableFields;
            }

            // 1. Get Main Table Columns
            $mainColumns = SchemaColumnsCache::get($tableName);
            if (! empty($mainColumns)) {
                $filterableFields['Main Table'] = [];
                foreach ($mainColumns as $col) {
                    $filterableFields['Main Table'][$col] = ['label' => $col, 'isRelation' => false];
                }
            } else {
                // Logic fallback: maybe use $config->table->columns
                $configCols = [];
                if (isset($config->table->columns)) {
                    foreach ($config->table->columns as $col) {
                        $name = $col->name ?? $col['name'] ?? null;
                        if ($name) {
                            $configCols[$name] = ['label' => $name, 'isRelation' => false];
                        }
                    }
                }
                $filterableFields['Main Table'] = ! empty($configCols) ? $configCols : ['id' => ['label' => 'id', 'isRelation' => false]];
            }

            // 2. Discover Relationships via Reflection
            $reflection = new \ReflectionClass($instance);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            // Known non-relation methods to skip
            $skipMethods = ['replicate', 'delete', 'forceDelete', 'push', 'save', 'getAttributes', 'getOriginal', 'toArray', 'toJson'];

            foreach ($methods as $method) {
                $name = $method->getName();

                // Basics check
                if ($method->getNumberOfParameters() > 0) {
                    continue;
                }
                if (str_starts_with($name, '__')) {
                    continue;
                }
                if (in_array($name, $skipMethods)) {
                    continue;
                }

                try {
                    // Check return type hint first (fastest)
                    $returnType = $method->getReturnType();
                    $isRelation = false;
                    if ($returnType instanceof \ReflectionNamedType) {
                        if (is_subclass_of($returnType->getName(), Relation::class)) {
                            $isRelation = true;
                        }
                    }

                    // Fallback
                    if (! $isRelation) {
                        if ($method->getDeclaringClass()->getName() !== Model::class) {
                            $result = $method->invoke($instance);
                            if ($result instanceof Relation) {
                                $isRelation = true;
                            }
                        }
                    }

                    if ($isRelation) {
                        $relation = $method->invoke($instance);
                        $relatedModel = $relation->getRelated();
                        $relatedTable = $relatedModel->getTable();
                        $relatedColumns = SchemaColumnsCache::get($relatedTable);

                        if (! empty($relatedColumns)) {
                            $filterableFields[$name] = [];
                            foreach ($relatedColumns as $col) {
                                $filterableFields[$name][$name.'.'.$col] = [
                                    'label' => $col,
                                    'isRelation' => true,
                                    'relationName' => $name,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        } catch (\Throwable $e) {
            // Absolute fallback
            $filterableFields['Main Table'] = ['id' => ['label' => 'id', 'isRelation' => false]];
        }

        return $filterableFields;
    }
}
