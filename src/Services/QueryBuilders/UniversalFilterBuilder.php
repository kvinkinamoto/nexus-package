<?php

namespace Nodex\Nexus\Services\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class UniversalFilterBuilder
{
    /**
     * Apply an array of conditions to an Eloquent Builder.
     *
     * @param Builder $query
     * @param array $conditions
     * @return Builder
     */
    public static function apply(Builder $query, array $conditions): Builder
    {
        if (empty($conditions)) {
            return $query;
        }

        $mainTable = $query->getModel()->getTable();

        $query->where(function (Builder $q) use ($conditions, $mainTable) {
            $directConditions = [];
            $relationalGroups = [];
            $appliedRelations = [];

            foreach ($conditions as $condition) {
                $column = $condition['column'] ?? null;
                if (!$column) continue;

                $operator = strtoupper($condition['operator'] ?? '=');
                $value = $condition['value'] ?? null;
                $logic = strtoupper($condition['logic'] ?? 'AND');
                $selectedId = $condition['selectedId'] ?? null;

                // Skip if no value and not a NULL check
                if (!in_array($operator, ['IS NULL', 'IS NOT NULL']) && ($value === null || $value === '')) {
                    continue;
                }

                if (str_contains($column, '.')) {
                    $parts = explode('.', $column);
                    $field = array_pop($parts);
                    $relationPath = implode('.', $parts);

                    $relationalGroups[$relationPath][] = [
                        'field' => $field,
                        'operator' => $operator,
                        'value' => $value,
                        'logic' => $logic,
                        'selectedId' => $selectedId
                    ];
                } else {
                    $directConditions[] = [
                        'column' => $column,
                        'operator' => $operator,
                        'value' => $value,
                        'logic' => $logic
                    ];
                }
            }

            // Apply Direct Conditions
            foreach ($directConditions as $idx => $cond) {
                $method = ($idx === 0 || $cond['logic'] === 'AND') ? 'where' : 'orWhere';
                $field = str_contains($cond['column'], '.') ? $cond['column'] : $mainTable . '.' . $cond['column'];
                $op = $cond['operator'];

                // Validate column existence
                $columnName = str_contains($cond['column'], '.') ? explode('.', $cond['column'])[1] : $cond['column'];
                $targetTable = str_contains($cond['column'], '.') ? explode('.', $cond['column'])[0] : $mainTable;

                if (!\Illuminate\Support\Facades\Schema::hasColumn($targetTable, $columnName)) {
                    session()->flash('alert_message', __('nexus::translate.filter_column_not_found', ['column' => $columnName, 'table' => $targetTable]));
                    session()->flash('alert_type', 'warning');
                    continue;
                }

                // Handle translatable fields
                if ($op === '=' && method_exists($q->getModel(), 'isTranslatableAttribute') && $q->getModel()->isTranslatableAttribute($cond['column'])) {
                    $op = 'LIKE';
                }

                self::addWhereClause($q, $field, $op, $cond['value'], $method);
            }

            // Apply Relational Conditions
            foreach ($relationalGroups as $path => $groupConds) {
                $baseRelation = explode('.', $path)[0];
                if (!method_exists($q->getModel(), $baseRelation)) {
                    continue;
                }

                $firstLogic = $groupConds[0]['logic'];
                $hasPriorConditions = count($directConditions) > 0 || !empty($appliedRelations);
                $relationMethod = ($firstLogic === 'OR' && $hasPriorConditions) ? 'orWhereHas' : 'whereHas';

                $appliedRelations[] = $path;

                // Special handling for Spatie Permissions to include roles inheritance
                if ($path === 'permissions' && method_exists($q->getModel(), 'roles')) {
                    $q->$relationMethod($path, function (Builder $relQ) use ($groupConds) {
                        self::applyConditionsToGroup($relQ, $groupConds);
                    });

                    // Add OR logic for roles.permissions (always OR within this specific permission group)
                    $q->orWhereHas('roles.permissions', function (Builder $relQ) use ($groupConds) {
                        self::applyConditionsToGroup($relQ, $groupConds);
                    });
                } else {
                    $q->$relationMethod($path, function (Builder $relQ) use ($groupConds) {
                        self::applyConditionsToGroup($relQ, $groupConds);
                    });
                }
            }
        });


        return $query;
    }

    /**
     * Apply a group of related conditions (sharing same relation path) to a query builder.
     */
    protected static function applyConditionsToGroup(Builder $relQ, array $groupConds)
    {
        $model = $relQ->getModel();
        $tableName = $model->getTable();
        $primaryKey = $model->getKeyName();

        foreach ($groupConds as $idx => $cond) {
            $method = ($idx === 0 || $cond['logic'] === 'AND') ? 'where' : 'orWhere';

            // Validate column existence
            if (!empty($cond['field']) && !\Illuminate\Support\Facades\Schema::hasColumn($tableName, $cond['field'])) {
                session()->flash('alert_message', __('nexus::translate.filter_relation_column_not_found', ['column' => $cond['field'], 'relation' => $tableName]));
                session()->flash('alert_type', 'warning');
                continue;
            }

            if (!empty($cond['selectedId'])) {
                $targetField = $tableName . '.' . $primaryKey;
                $targetOp = '=';
                $targetValue = $cond['selectedId'];
            } else {
                $targetField = str_contains($cond['field'], '.') ? $cond['field'] : $tableName . '.' . $cond['field'];
                $targetOp = $cond['operator'];
                $targetValue = $cond['value'];

                // Handle translatable fields
                if ($targetOp === '=' && method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($cond['field'])) {
                    $targetOp = 'LIKE';
                }
            }

            self::addWhereClause($relQ, $targetField, $targetOp, $targetValue, $method);
        }
    }

    /**
     * Add the actual where clause to the builder based on the operator.
     */
    protected static function addWhereClause(Builder $query, string $field, string $operator, mixed $value, string $method)
    {
        switch ($operator) {
            case 'LIKE':
            case 'NOT LIKE':
                $query->$method($field, $operator, "%{$value}%");
                break;
            case 'STARTS_WITH':
                $query->$method($field, 'LIKE', "{$value}%");
                break;
            case 'ENDS_WITH':
                $query->$method($field, 'LIKE', "%{$value}");
                break;
            case 'IN':
                $inMethod = $method === 'orWhere' ? 'orWhereIn' : 'whereIn';
                $values = is_array($value) ? $value : array_map('trim', explode(',', $value));
                $query->$inMethod($field, $values);
                break;
            case 'NOT IN':
                $inMethod = $method === 'orWhere' ? 'orWhereNotIn' : 'whereNotIn';
                $values = is_array($value) ? $value : array_map('trim', explode(',', $value));
                $query->$inMethod($field, $values);
                break;
            case 'BETWEEN':
                $betweenMethod = $method === 'orWhere' ? 'orWhereBetween' : 'whereBetween';
                $values = is_array($value) ? $value : array_map('trim', explode(',', $value));
                if (count($values) >= 2) {
                    $query->$betweenMethod($field, [$values[0], $values[1]]);
                }
                break;
            case 'IS NULL':
                $nullMethod = $method === 'orWhere' ? 'orWhereNull' : 'whereNull';
                $query->$nullMethod($field);
                break;
            case 'IS NOT NULL':
                $nullMethod = $method === 'orWhere' ? 'orWhereNotNull' : 'whereNotNull';
                $query->$nullMethod($field);
                break;
            default:
                // For =, >, <, >=, <=, !=
                $query->$method($field, $operator, $value);
                break;
        }
    }
}
