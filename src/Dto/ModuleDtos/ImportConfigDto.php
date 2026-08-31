<?php

namespace Nodex\Nexus\Dto\ModuleDtos;

/**
 * Mirrors ExportConfigDto's shape, minus chunkSize — unlike export (queued,
 * chunked, default-enabled on every module), import is deliberately v1-scoped
 * to a single synchronous request and is opt-in per module (see
 * Attributes/TableImport.php) rather than auto-registered, since it writes
 * data rather than just reading it.
 */
class ImportConfigDto extends \stdClass
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

    public static function fromArray(array|\stdClass $action): ImportConfigDto
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
