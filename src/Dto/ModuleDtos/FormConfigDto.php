<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class FormConfigDto extends \stdClass
{
    public function __construct(public array $fields = [])
    {
    }

    public function field(string $name, string $type, string $section = 'default', ?string $label = null): FieldConfigDto
    {
        $field = new FieldConfigDto($name, $type, $section, $label);
        $this->fields[$name] = $field;
        return $field;
    }

    public static function fromArray(array|\stdClass $form): FormConfigDto
    {
        $form = (object) $form;
        $fields = [];
        foreach ($form->fields as $key => $field) {
            $fields[$key] = FieldConfigDto::fromArray($field);
        }
        return new self($fields);
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function toArray(): array
    {
        return [
            'fields' => array_map(fn($field) => $field->toArray(), $this->fields),
        ];
    }
}
