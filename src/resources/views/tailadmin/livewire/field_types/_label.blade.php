{{--
    Shared field label, factored out of string.blade.php so text/number/
    boolean can reuse it byte-for-byte instead of drifting apart. Expects
    $field in scope.
--}}
<label for="field-{{ $field->name }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
    {{ nexus_trans_label($module->name, $field->label ?? null, $field->name) }}
    @if($field->isRequired) <span class="text-error-500">*</span> @endif
</label>
