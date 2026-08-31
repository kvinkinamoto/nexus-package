<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Http\FormRequest;

class GatheringValidationRules
{
    public function __construct(
        public FormRequest $request,
        public array &$rules
    ) {}
}
