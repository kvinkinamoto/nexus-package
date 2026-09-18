<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class SettingConfigDto extends \stdClass
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $label = null,
        public mixed $default = null,
        public bool $isRequired = false,
        public array $options = [],
        public bool $isMultiple = false,
        public ?string $comment = null,
        public ?string $view = null,
    ) {
        $this->label = $label ?? ucfirst($this->name);
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->isRequired = $required;
        return $this;
    }

    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function multiple(bool $multiple = true): self
    {
        $this->isMultiple = $multiple;
        return $this;
    }

    public function comment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function view(string $view): self
    {
        $this->view = $view;
        return $this;
    }

    public static function fromArray(\stdClass $data): self
    {
        return new self(
            name: $data->name ?? '',
            type: $data->type ?? 'string',
            label: $data->label ?? null,
            default: $data->default ?? null,
            isRequired: $data->isRequired ?? false,
            options: (array) ($data->options ?? []),
            isMultiple: $data->isMultiple ?? false,
            comment: $data->comment ?? null,
            view: $data->view ?? null,
        );
    }
}
