<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class MethodResource
{
    public function __construct(
        public readonly string $method,
        public readonly string $resourceClass,
        public readonly array $with = [],
    ) {}
}
