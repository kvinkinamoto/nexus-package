@php
    $errorKey = "relationRows.{$field->name}.{$index}.{$column->name}";
@endphp
<div class="flex items-center gap-2">
    <input type="color" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}"
        class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-gray-300 bg-transparent p-1 dark:border-gray-700">
    <input type="text" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}"
        placeholder="#000000"
        class="h-10 w-full rounded-lg border bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden dark:bg-gray-900 dark:text-white/90 {{ $errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700' }}">
</div>
@error($errorKey)
    <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
@enderror
