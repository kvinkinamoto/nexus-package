{{--
    Livewire Етап 6 — read-only pretty-printed JSON display. ActivityLog's
    `properties` is the only user; ModuleForm::mount() already pre-formats
    the value into $this->data (see its 'json' branch) since a raw
    array/Collection can't bind through wire:model as a scalar.
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'w-full rounded-lg border bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <textarea rows="6" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="{{ $inputClass }}"></textarea>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
