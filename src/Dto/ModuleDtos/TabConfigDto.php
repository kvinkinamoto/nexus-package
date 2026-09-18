<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class TabConfigDto extends \stdClass
{
    public string $name;
    public string $label;

    public function __construct(string $name, string $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public static function fromArray(\stdClass $sec): TabConfigDto
    {
        return new self(
            name: $sec->name,
            label: $sec->label,
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
}
