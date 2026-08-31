<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class TableFilter
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type,
    ) {}
}
