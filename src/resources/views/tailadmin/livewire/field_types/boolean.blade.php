{{-- Livewire Етап 6 — boolean switch. Not translatable; a boolean has no locale variants. --}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-4">
    <label class="flex cursor-pointer items-center gap-2.5">
        <span class="relative inline-flex items-center">
            <input type="checkbox" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
                @disabled($field->isDisabledForAction($action ?? null))
                class="peer sr-only">
            <span class="h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700"></span>
            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
        </span>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-400">
            {{ nexus_trans_label($module->name, $field->label ?? null, $field->name) }}
        </span>
    </label>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
