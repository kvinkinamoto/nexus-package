<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

class ActionGroupConfigDto extends \stdClass
{
    public string $name;
    public string $action;
    public bool $confirm;
    public array $params;
    public bool $isActive;

    public function __construct(string $name, string $action, bool $confirm = true, array $params = [], bool $isActive = true)
    {
        $this->name = $name;
        $this->action = $action;
        $this->confirm = $confirm;
        $this->params = $params;
        $this->isActive = $isActive;
    }

    public function action(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function confirm(bool $confirm = true): self
    {
        $this->confirm = $confirm;
        return $this;
    }

    public function params(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public static function fromArray(array|\stdClass $actionGroup): ActionGroupConfigDto
    {
        $actionGroup = (object) $actionGroup;
        return new self(
            $actionGroup->name,
            $actionGroup->action,
            $actionGroup->confirm ?? true,
            (array) ($actionGroup->params ?? []),
            $actionGroup->isActive ?? true,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAction(): string
    {
        return $this->action;
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
            'action' => $this->action,
            'confirm' => $this->confirm,
            'params' => $this->params,
            'isActive' => $this->isActive,
        ];
    }
}
