<?php

namespace Nodex\Nexus\Attributes;

use Attribute;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Associates custom FormRequests with a Nexus module's actions, keyed by action name
 * (e.g. 'store', 'update', 'restore', 'boolToggle', or any custom group action name).
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Requests
{
    /** @param array<string, class-string<FormRequest>> $actions */
    public function __construct(
        public readonly array $actions = [],
    ) {}
}
