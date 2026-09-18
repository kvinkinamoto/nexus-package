<?php

namespace Nodex\Nexus\Requests\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IntegerOrUuid implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!ctype_digit((string)$value) && !preg_match('/^[0-9a-fA-F-]{36}$/', $value)) {
            $fail("Поле {$attribute} повинно бути цілим числом або UUID.");
        }
    }
}
