<?php

namespace Nodex\Nexus\Events;

use Illuminate\Foundation\Http\FormRequest;

class PreparingForValidation
{
    public function __construct(
        public FormRequest $request
    ) {}
}
