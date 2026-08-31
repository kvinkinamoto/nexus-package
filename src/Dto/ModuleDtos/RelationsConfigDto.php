<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class RelationsConfigDto extends \stdClass
{
    public array $available;

    public function __construct(array $available)
    {
        $this->is_available = $available;
    }

    public static function fromArray(array|\stdClass $relations)
    {
        $relations = (object) $relations;
        $available = [];

        foreach ($relations->is_available as $key => $field) {
            $available[$key] = RelationConfigDto::fromArray($field);
        }

        return new self(
            available: $available,
        );
    }

    public function toArray(): array
    {
        return [
            'is_available' => array_map(fn($relation) => $relation->toArray(), $this->is_available),
        ];
    }
}
