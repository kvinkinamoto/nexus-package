<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class ColumnConfigDto extends \stdClass
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public bool $sortable = false,
        public ?string $action = null,
        public ?string $fieldName = null,
        public bool $actionConfirm = false,
        public ?string $customField = null,
        public bool $tableDefault = true,
        public int $order = 0,
        public bool $searchable = false,
    ) {
        $this->label = $label ?? $this->name;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function action(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function fieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function actionConfirm(bool $confirm = true): self
    {
        $this->actionConfirm = $confirm;
        return $this;
    }

    public function customField(string $customField): self
    {
        $this->customField = $customField;
        return $this;
    }

    public static function fromArray(mixed $column): ColumnConfigDto
    {
        $column = (object) $column;
        return new self(
            name: $column->name,
            label: $column->label ?? null,
            sortable: $column->sortable ?? false,
            action: $column->action ?? null,
            fieldName: $column->fieldName ?? null,
            actionConfirm: $column->actionConfirm ?? false,
            customField: $column->customField ?? null,
            tableDefault: $column->tableDefault ?? true,
            order: $column->order ?? 0,
            searchable: $column->searchable ?? false,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'sortable' => $this->sortable,
            'action' => $this->action,
            'fieldName' => $this->fieldName,
            'actionConfirm' => $this->actionConfirm,
            'customField' => $this->customField,
            'tableDefault' => $this->tableDefault,
            'order' => $this->order,
            'searchable' => $this->searchable,
        ];
    }

    public function tableDefault(bool $default = true): self
    {
        $this->tableDefault = $default;
        return $this;
    }
}
