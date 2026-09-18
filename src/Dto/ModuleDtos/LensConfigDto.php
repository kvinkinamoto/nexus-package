<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class LensConfigDto extends \stdClass
{
    public string $name;
    public string $label;
    public array $conditions;
    public ?string $icon;
    public ?array $columns;
    public ?string $sort;

    public function __construct(
        string $name,
        string $label,
        array $conditions = [],
        ?string $icon = null,
        ?array $columns = null,
        ?string $sort = null,
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->conditions = $conditions;
        $this->icon = $icon;
        $this->columns = $columns;
        $this->sort = $sort;
    }

    public static function fromArray(\stdClass $lens): self
    {
        return new self(
            name: $lens->name,
            label: $lens->label,
            conditions: (array) ($lens->conditions ?? []),
            icon: $lens->icon ?? null,
            columns: isset($lens->columns) ? (array) $lens->columns : null,
            sort: $lens->sort ?? null,
        );
    }
}
