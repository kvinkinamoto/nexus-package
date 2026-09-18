<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Opts a module into CSV import — unlike #[TableAction]/#[TableGroupAction]
 * siblings, import has no auto-registered default (see
 * DefaultModuleConfigurationDto's default 'export' entry vs the absence of
 * one for imports): it writes data, so a module must explicitly declare it
 * wants this.
 *
 * Example:
 *   #[TableImport(name: 'import', label: 'Import')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class TableImport
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $label = null,
        public readonly string $icon = '',
        public readonly bool $confirm = true,
        public readonly bool $isActive = true,
    ) {}
}
