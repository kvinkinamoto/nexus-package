<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class SectionConfigDto extends \stdClass
{
    public string $name;
    public string $column;
    public ?string $type;
    public ?string $icon;
    public ?string $class = '';

    public function __construct(string $name, string $column, ?string $type = null, ?string $icon = null)
    {
        $this->name = $name;
        $this->column = $column;
        $this->type = $type;
        $this->icon = $icon;
    }

    public function column(string $column): self
    {
        $this->column = $column;
        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public static function fromArray(\stdClass $sec): SectionConfigDto
    {
        //        dd($sec);
        $fields = [];
        //        foreach ($form->fields as $key => $field) {
//            $fields[$key] = FieldConfigDto::fromArray($field);
//        }
//        return new self($fields);

        return new self(
            name: $sec->name,
            column: $sec->column,
            type: $sec->type ?? null,
            icon: $sec->icon ?? null,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

}
