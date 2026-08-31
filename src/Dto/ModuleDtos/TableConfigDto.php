<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

use Nodex\Nexus\Enums\AdminAvailableFilterEnum;

// Клас для конфігурації таблиці
class TableConfigDto extends \stdClass
{
    public ?array $columns;
    public array $filters;
    public array $actions;
    public array $mainActions;
    public array $actionGroup;
    public array $exports;
    public array $imports = [];
    public array $universalFilters = [];

    public ?string $defaultSortColumn = null;
    public string $defaultSortDirection = 'asc';

    /**
     * @param array<string, ColumnConfigDto> $columns Масив, де ключі — рядки, а значення — об’єкти ColumnConfigDto
     * @param array<string, FilterConfigDto> $filters Масив, де ключі — рядки, а значення — об’єкти ColumnConfigDto
     * @param array<string, ActionConfigDto> $actions Масив, де ключі — рядки, а значення — об’єкти ColumnConfigDto
     * @param array<string, ActionGroupConfigDto> $actionGroup Масив, де ключі — рядки, а значення — об’єкти ColumnConfigDto
     */
    public function __construct(array $columns = [], array $filters = [], array $actions = [], array $mainActions = [], array $actionGroup = [], array $exports = [], array $imports = [])
    {
        $this->columns = $columns;
        $this->filters = $filters;
        $this->mainActions = $mainActions;
        $this->actions = $actions;
        $this->actionGroup = $actionGroup;
        $this->exports = $exports;
        $this->imports = $imports;
    }

    public function column(string $name, ?string $label = null): ColumnConfigDto
    {
        $column = new ColumnConfigDto($name, $label);
        $this->columns[$name] = $column;
        return $column;
    }

    public function filter(string $name, ?string $label = null, ?string $type = null): FilterConfigDto
    {
        $filter = new FilterConfigDto($name, $label, $type ?? AdminAvailableFilterEnum::FILTER_SEARCH->value);
        $this->filters[$name] = $filter;
        return $filter;
    }

    public function action(string $name, ?string $label = null, string $icon = '', bool $confirm = true, bool $isActive = true): ActionConfigDto
    {
        $action = new ActionConfigDto($name, $label, $icon, $confirm, $isActive);
        $this->actions[$name] = $action;
        return $action;
    }

    public function mainAction(string $name, ?string $label = null, string $icon = '', bool $confirm = true, bool $isActive = true): ActionConfigDto
    {
        $action = new ActionConfigDto($name, $label, $icon, $confirm, $isActive);
        $this->mainActions[$name] = $action;
        return $action;
    }

    public function group(string $name, string $actionName, bool $confirm = true, array $params = [], bool $isActive = true): ActionGroupConfigDto
    {
        $group = new ActionGroupConfigDto($name, $actionName, $confirm, $params, $isActive);
        $this->actionGroup[$name] = $group;
        return $group;
    }

    public function export(string $name, ?string $label = null, string $icon = '', bool $confirm = true, bool $isActive = true, int $chunkSize = 5000): ExportConfigDto
    {
        $action = new ExportConfigDto($name, $label, $icon, $confirm, $isActive, $chunkSize);
        $this->exports[$name] = $action;
        return $action;
    }

    public function import(string $name, ?string $label = null, string $icon = '', bool $confirm = true, bool $isActive = true): ImportConfigDto
    {
        $import = new ImportConfigDto($name, $label, $icon, $confirm, $isActive);
        $this->imports[$name] = $import;
        return $import;
    }

    public function universalFilter(string $tableOrRelationName, array $fields): self
    {
        $this->universalFilters[$tableOrRelationName] = $fields;
        return $this;
    }

    /**
     * Set the default sort column and direction for the index table.
     *
     * @param string $column    The column name to sort by default.
     * @param string $direction 'asc' or 'desc'.
     * @return $this
     */
    public function defaultSort(string $column, string $direction = 'asc'): self
    {
        $this->defaultSortColumn = $column;
        $this->defaultSortDirection = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        return $this;
    }

    public static function fromArray(array|\stdClass $table): TableConfigDto
    {
        $table = (object) $table;
        $columns = [];
        if (!empty($table->columns)) {
            foreach ($table->columns as $column) {
                $columns[] = ColumnConfigDto::fromArray($column);
            }
        }

        $filters = [];
        if (!empty($table->filters)) {
            foreach ($table->filters as $filter) {
                $filters[] = FilterConfigDto::fromArray($filter);
            }
        }

        $actions = [];
        if (!empty($table->actions)) {
            foreach ($table->actions as $action) {
                $actions[] = ActionConfigDto::fromArray($action);
            }
        }

        $mainActions = [];
        if (!empty($table->mainActions)) {
            foreach ($table->mainActions as $tableAction) {
                $mainActions[] = ActionConfigDto::fromArray($tableAction);
            }
        }

        $actionGroup = [];
        if (!empty($table->actionGroup)) {
            foreach ($table->actionGroup as $actionGroupItem) {
                $actionGroup[] = ActionGroupConfigDto::fromArray($actionGroupItem);
            }
        }

        $exports = [];
        if (!empty($table->exports)) {
            foreach ($table->exports as $export) {
                $exports[] = ExportConfigDto::fromArray($export);
            }
        }

        $imports = [];
        if (!empty($table->imports)) {
            foreach ($table->imports as $import) {
                $imports[] = ImportConfigDto::fromArray($import);
            }
        }

        $universalFilters = [];
        if (!empty($table->universalFilters)) {
            $universalFilters = (array) $table->universalFilters;
        }

        $instance = new self($columns, $filters, $actions, $mainActions, $actionGroup, $exports, $imports);
        $instance->universalFilters = $universalFilters;
        $instance->defaultSortColumn = $table->defaultSortColumn ?? null;
        $instance->defaultSortDirection = $table->defaultSortDirection ?? 'asc';
        return $instance;
    }

    public function toArray(): array
    {
        return [
            'columns' => array_map(fn($column) => $column->toArray(), $this->columns),
            'filters' => array_map(fn($filter) => $filter->toArray(), $this->filters),
            'actions' => array_map(fn($action) => $action->toArray(), $this->actions),
            'mainActions' => array_map(fn($mainActions) => $mainActions->toArray(), $this->mainActions),
            'actionGroup' => array_map(fn($actionGroup) => $actionGroup->toArray(), $this->actionGroup),
            'exports' => array_map(fn($exports) => $exports->toArray(), $this->exports),
            'imports' => array_map(fn($imports) => $imports->toArray(), $this->imports),
            'universalFilters' => $this->universalFilters,
            'defaultSortColumn' => $this->defaultSortColumn,
            'defaultSortDirection' => $this->defaultSortDirection,
        ];
    }

}
