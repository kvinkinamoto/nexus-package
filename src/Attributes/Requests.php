<?php

namespace Nodex\Nexus\Attributes;

use Attribute;

/**
 * Associates custom FormRequests for the store and update actions of a Nexus module.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Requests
{
    public function __construct(
        public readonly ?string $store = null,
        public readonly ?string $update = null,
    ) {}
}
