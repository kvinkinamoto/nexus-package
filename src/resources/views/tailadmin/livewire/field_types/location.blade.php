{{--
    Port of templates/field_types/location.blade.php — that legacy partial
    was already just a plain labeled text input with a location icon (not an
    actual map/coordinate picker), so this stays exactly that, same shape as
    email.blade.php.
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent pl-10 pr-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="relative">
        <i class="{{ nexus_icon('location') }} pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="{{ $inputClass }}">
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
