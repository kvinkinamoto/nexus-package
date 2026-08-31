<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Composer
{
    public function __construct(
        public readonly string $class,
    ) {}
}
