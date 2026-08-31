<?php

namespace Nodex\Nexus\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Nodex\Nexus\Requests\Rules\IntegerOrUuid;

class DefaultGroupActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*' => ['required', new IntegerOrUuid],
        ];
    }

    protected function prepareForValidation()
    {

    }
}
