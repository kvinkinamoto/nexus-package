<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class SectionColumnConfigDto extends \stdClass
{
    public string $name;
    public string $class;
    public ?string $tab;

    public function __construct(string $name, string $class, ?string $tab = null)
    {
        $this->name = $name;
        $this->class = $class;
        $this->tab = $tab;
    }

    public function class(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function tab(string $tab): self
    {
        $this->tab = $tab;
        return $this;
    }

    public static function fromArray(\stdClass $col): SectionColumnConfigDto
    {
        return new self(
            name: $col->name ?? 'default',
            class: $col->class ?? 'col-span-12',
            tab: $col->tab ?? null,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getTab(): string
    {
        return $this->tab;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'class' => $this->class,
            'tab' => $this->tab,
        ];
    }
}
