@php
    $errorKey = "relationRows.{$field->name}.{$index}.{$column->name}";
@endphp
<label class="flex h-10 items-center justify-center">
    <input type="checkbox" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}"
        class="h-5 w-5 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900">
</label>
@error($errorKey)
    <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
@enderror
