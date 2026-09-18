<?php

namespace Nodex\Nexus\Services\Actions\Admin;

use Illuminate\Database\Eloquent\Builder;

class AddSorterActionMethod
{
    /**
     * Apply sorting to the query.
     *
     * If $inputColumns is provided and matches a sortable column, it is used.
     * Otherwise, falls back to $defaultSortColumn / $defaultSortDirection.
     * For tree models (NodeTrait), a subquery is used to sort by root-node value
     * while maintaining parent-child hierarchy via a secondary _lft asc order.
     *
     * @param Builder     $query
     * @param array       $columns            Array of ColumnConfigDto objects.
     * @param string|null $inputColumns       Value of the 'sort' request parameter (e.g. '-created_at').
     * @param string|null $defaultSortColumn  Column name to use as default sort.
     * @param string      $defaultSortDirection 'asc' or 'desc'.
     * @return Builder
     */
    public static function handle(
        Builder $query,
        array $columns,
        ?string $inputColumns = '',
        ?string $defaultSortColumn = null,
        string $defaultSortDirection = 'asc',
        bool $isTree = false
    ): Builder {
        // Розбиваємо рядок по комі
        $sortItems = explode(',', $inputColumns ?? '');

        $sortColumns = [];

        foreach ($sortItems as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            // Перевіряємо, чи починається елемент із "-"
            $direction = (str_starts_with($item, '-') ? 'desc' : 'asc');
            // Отримуємо назву колонки, видаляючи "-" якщо він є
            $column = ltrim($item, '-');

            // Зберігаємо колонку та напрям сортування
            $sortColumns[$column] = $direction;
        }

        $sortApplied = false;

        foreach ($columns as $column) {
            if (key_exists($column->name, $sortColumns) && isset($column->sortable) && $column->sortable === true) {
                self::applySort($query, $column->name, $sortColumns[$column->name], $isTree);
                $sortApplied = true;
                break;
            }
        }

        // Fallback to default sort when no explicit sort was applied
        if (!$sortApplied && $defaultSortColumn !== null) {
            self::applySort($query, $defaultSortColumn, $defaultSortDirection, $isTree);
            $sortApplied = true;
        }

        // For tree models with no custom sort, apply the natural tree order (_lft asc)
        if (!$sortApplied) {
            $model = $query->getModel();
            $traits = class_uses_recursive($model);
            if ($isTree && in_array(\Kalnoy\Nestedset\NodeTrait::class, $traits)) {
                $query->orderBy($model->getLftName(), 'asc');
            }
        }

        return $query;
    }

    /**
     * Apply sorting to the query, with special handling for Kalnoy\Nestedset models.
     *
     * For tree models, uses a correlated subquery to find the root-node's value of
     * the sort column. This groups all descendants under their sorted root, and
     * a secondary _lft asc order keeps each family in natural hierarchical order.
     *
     * @param Builder $query
     * @param string  $column
     * @param string  $direction
     * @return void
     */
    protected static function applySort(Builder $query, string $column, string $direction, bool $isTree = false): void
    {
        $model  = $query->getModel();
        $traits = class_uses_recursive($model);

        if ($isTree && in_array(\Kalnoy\Nestedset\NodeTrait::class, $traits)) {
            $lft    = $model->getLftName();
            $rgt    = $model->getRgtName();
            $parent = $model->getParentIdName();
            $table  = $model->getTable();

            $driverName = $query->getConnection()->getDriverName();
            $q = ($driverName === 'pgsql' || $driverName === 'oracle') ? '"' : '`';

            // Detect if the column is a translatable (Spatie JSON) attribute
            $isTranslatable = isset($model->translatable) && in_array($column, $model->translatable);

            if ($isTranslatable && $driverName === 'mysql') {
                $locale   = app()->getLocale();
                $subquery = "(SELECT root.{$q}{$column}{$q}->>'$.{$locale}' FROM {$q}{$table}{$q} AS root WHERE root.{$q}{$lft}{$q} <= {$q}{$table}{$q}.{$q}{$lft}{$q} AND root.{$q}{$rgt}{$q} >= {$q}{$table}{$q}.{$q}{$rgt}{$q} AND root.{$q}{$parent}{$q} IS NULL LIMIT 1)";
            } elseif ($isTranslatable && $driverName === 'pgsql') {
                $locale   = app()->getLocale();
                $subquery = "(SELECT root.{$q}{$column}{$q}->>'{$locale}' FROM {$q}{$table}{$q} AS root WHERE root.{$q}{$lft}{$q} <= {$q}{$table}{$q}.{$q}{$lft}{$q} AND root.{$q}{$rgt}{$q} >= {$q}{$table}{$q}.{$q}{$rgt}{$q} AND root.{$q}{$parent}{$q} IS NULL LIMIT 1)";
            } else {
                $subquery = "(SELECT root.{$q}{$column}{$q} FROM {$q}{$table}{$q} AS root WHERE root.{$q}{$lft}{$q} <= {$q}{$table}{$q}.{$q}{$lft}{$q} AND root.{$q}{$rgt}{$q} >= {$q}{$table}{$q}.{$q}{$rgt}{$q} AND root.{$q}{$parent}{$q} IS NULL LIMIT 1)";
            }

            // Primary: sort by root node's column value (groups families together)
            $query->orderByRaw("{$subquery} {$direction}");
            // Secondary: natural tree order within each family group
            $query->orderBy($lft, 'asc');

        } else {
            // For standard flat sorting, check if translatable
            $isTranslatable = isset($model->translatable) && in_array($column, $model->translatable);
            if ($isTranslatable) {
                $driverName = $query->getConnection()->getDriverName();
                $q = ($driverName === 'pgsql' || $driverName === 'oracle') ? '"' : '`';
                $locale = app()->getLocale();
                if ($driverName === 'mysql') {
                    $query->orderByRaw("{$q}{$column}{$q}->>'$.{$locale}' {$direction}");
                } elseif ($driverName === 'pgsql') {
                    $query->orderByRaw("{$q}{$column}{$q}->>'{$locale}' {$direction}");
                } else {
                    $query->orderBy($column, $direction);
                }
            } else {
                $query->orderBy($column, $direction);
            }
        }
    }
}
