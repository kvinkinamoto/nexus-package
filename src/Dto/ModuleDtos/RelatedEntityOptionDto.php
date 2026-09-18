<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class RelatedEntityOptionDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $morphId,
        public readonly ?string $label = null,
        public readonly ?string $customFieldName = null,
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    public function morphId(): ?string
    {
        return $this->morphId;
    }

    public function customFieldName(): ?string
    {
        return $this->customFieldName;
    }
}
