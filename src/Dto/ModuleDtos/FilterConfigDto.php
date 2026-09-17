<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

use Nodex\Nexus\Enums\AdminAvailableFilterEnum;

class FilterConfigDto extends \stdClass
{
    public string $name;
    public string $label;
    public string $type;
    public ?string $optionsModel;
    public string $optionsValue;
    public string $optionsLabel;

    public function __construct(
        string $name,
        ?string $label = null,
        string $type = AdminAvailableFilterEnum::FILTER_SEARCH->value,
        ?string $optionsModel = null,
        string $optionsValue = 'id',
        string $optionsLabel = 'name',
    ) {
        $this->name = $name;
        $this->label = $label ?? $this->name;
        $this->type = $type;
        $this->optionsModel = $optionsModel;
        $this->optionsValue = $optionsValue;
        $this->optionsLabel = $optionsLabel;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public static function fromArray(array|\stdClass $filter): FilterConfigDto
    {
        $filter = (object) $filter;
        return new self(
            name: $filter->name,
            label: $filter->label ?? null,
            type: $filter->type ?? AdminAvailableFilterEnum::FILTER_SEARCH->value,
            optionsModel: $filter->optionsModel ?? null,
            optionsValue: $filter->optionsValue ?? 'id',
            optionsLabel: $filter->optionsLabel ?? 'name',
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'optionsModel' => $this->optionsModel,
            'optionsValue' => $this->optionsValue,
            'optionsLabel' => $this->optionsLabel,
        ];
    }
}
