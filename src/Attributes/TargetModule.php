<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class TargetModule
{
    public function __construct(
        public string $name
    ) {
    }
}
