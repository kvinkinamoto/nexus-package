{{--
    Shared field label, factored out of string.blade.php so text/number/
    boolean can reuse it byte-for-byte instead of drifting apart. Expects
    $field in scope.
--}}
<label for="field-{{ $field->name }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
    @if(str_contains($field->label ?? '', '::'))
        @lang($field->label)
    @else
        @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($field->label ?? $field->name))
    @endif
    @if($field->isRequired) <span class="text-error-500">*</span> @endif
</label>
