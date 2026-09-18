<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class ActionConfigDto extends \stdClass
{
    public string $name;
    public string $label;
    public string $icon;
    public bool $confirm;
    public bool $isActive;

    public function __construct(string $name, ?string $label = null, string $icon = '', bool $confirm = true, bool $isActive = true)
    {
        $this->name = $name;
        $this->label = $label ?? $this->name;
        $this->icon = $icon;
        $this->confirm = $confirm;
        $this->isActive = $isActive;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function confirm(bool $confirm = true): self
    {
        $this->confirm = $confirm;
        return $this;
    }

    public static function fromArray(array|\stdClass $action): ActionConfigDto
    {
        $action = (object) $action;
        return new self(
            name: $action->name,
            label: $action->label ?? null,
            icon: $action->icon ?? '',
            confirm: $action->confirm ?? true,
            isActive: $action->isActive ?? true,
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

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function isConfirm(): bool
    {
        return $this->confirm;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'icon' => $this->icon,
            'confirm' => $this->confirm,
            'isActive' => $this->isActive,
        ];
    }
}
