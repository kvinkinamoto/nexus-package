<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class TableAction
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $icon,
        public readonly bool $isConfirm = false,

        /** Register as a table-level main action (e.g. "Create") instead of a per-row action. */
        public readonly bool $isMain = false,

        /** Whether the action is enabled. Set false to register but hide/disable it (e.g. disable "Create" for read-only/auto-generated data). */
        public readonly bool $isActive = true,
    ) {}
}
