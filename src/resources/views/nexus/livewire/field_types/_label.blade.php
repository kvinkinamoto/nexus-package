{{--
    Shared field label, factored out of string.blade.php so text/number/
    boolean can reuse it byte-for-byte instead of drifting apart. Expects
    $field in scope.
--}}
<label for="field-{{ $field->name }}" class="form-label">
    @if(str_contains($field->label ?? '', '::'))
        @lang($field->label)
    @else
        @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($field->label ?? $field->name))
    @endif
    @if($field->isRequired) * @endif
</label>
