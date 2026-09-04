{{--
    Free-text icon key (e.g. 'solar:widget-bold', 'bx bx-user' — whatever
    nexus_icon()/IconManager resolves) with a live preview, not a browsable
    icon gallery — this package has no bundled icon-set dataset to browse,
    only whatever CSS/webfont the active template already loads.
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-300 text-lg text-gray-600 dark:border-gray-700 dark:text-gray-300">
            <i class="{{ nexus_icon($this->data[$field->name] ?? null, null, 'bx bx-question-mark') }}"></i>
        </span>
        <input type="text" id="field-{{ $field->name }}" wire:model.live.debounce.400ms="data.{{ $field->name }}"
            placeholder="solar:widget-bold"
            @disabled($field->isDisabledForAction($action ?? null))
            class="{{ $inputClass }}">
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
