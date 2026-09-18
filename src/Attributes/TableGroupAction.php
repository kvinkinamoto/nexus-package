<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class TableGroupAction
{
    public function __construct(
        public readonly string $name,
        public readonly string $fieldName,
    ) {}
}
