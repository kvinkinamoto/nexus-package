{{--
    Livewire Етап 6 — port of templates/field_types/password.blade.php.
    Always renders empty (see ModuleForm::mount()'s 'password' branch) —
    on edit this is a "set a new password" field, not a display of the
    current one; leaving it blank and saving leaves the password unchanged
    (User's AdminUpdateRequest strips an empty password before validation).
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4" x-data="{ show: false }">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="relative">
        <input :type="show ? 'text' : 'password'" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            autocomplete="new-password"
            class="{{ $inputClass }} pr-10">
        <button type="button" @click="show = !show" tabindex="-1"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <i :class="show ? '{{ nexus_icon('eye_closed') }}' : '{{ nexus_icon('eye') }}'"></i>
        </button>
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
