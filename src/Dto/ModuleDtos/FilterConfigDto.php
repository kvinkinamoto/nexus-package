<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

use Nodex\Nexus\Enums\AdminAvailableFilterEnum;

class FilterConfigDto extends \stdClass
{
    public string $name;
    public string $label;
    public string $type;

    public function __construct(string $name, ?string $label = null, string $type = AdminAvailableFilterEnum::FILTER_SEARCH->value)
    {
        $this->name = $name;
        $this->label = $label ?? $this->name;
        $this->type = $type;
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
        ];
    }
}
