<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

// Клас для конфігурації меню
class MenuConfigDto extends \stdClass
{
    public ?bool $show;
    public string $name;
    public string $label;
    public ?string $parent = null;
    public string $icon;

    public function __construct(string $name, ?string $label = null, bool $show = true, ?string $parent = null, string $icon = '')
    {
        $this->show = $show;
        $this->name = $name;
        $this->label = $label ?? $name;
        $this->parent = $parent;
        $this->icon = $icon;
    }

    public static function fromArray(array|\stdClass $menu): MenuConfigDto
    {
        $menu = (object) $menu;
        return new self(
            name: $menu->name ?? 'ModuleName',
            label: $menu->label ?? null,
            show: $menu->show ?? true,
            parent: $menu->parent ?? null,
            icon: $menu->icon ?? '',
        );
    }

    public function isShow(): bool
    {
        return $this->show;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setShow(bool $show): self
    {
        $this->show = $show;
        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setParent(?string $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'show' => $this->show,
            'name' => $this->name,
            'label' => $this->label,
            'parent' => $this->parent,
            'icon' => $this->icon,
        ];
    }


}
