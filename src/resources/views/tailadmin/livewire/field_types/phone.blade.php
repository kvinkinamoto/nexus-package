{{--
    Livewire Етап 6 — port of templates/field_types/phone.blade.php. Same
    shape as string.blade.php with an icon; deliberately skips porting the
    legacy version's IMask.js input mask — that library initializes once via
    a DOMContentLoaded listener and has no hook to re-attach itself after a
    Livewire morph, so it would only ever mask on the very first paint and
    silently stop working after any reactive update. A plain text input with
    the same server-side format validation (see AdminUpdateRequest's phone
    regex rule) is honest about that gap rather than shipping a mask that
    looks like it works and then doesn't.
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent pl-10 pr-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="relative">
        <i class="{{ nexus_icon('phone') }} pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="{{ $inputClass }}">
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
