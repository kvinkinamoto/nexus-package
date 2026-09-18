{{--
    Deliberately a plain monospace textarea, no live preview — this package
    has no Markdown-rendering JS dependency today, and adding one is a
    decision for the app, not something to smuggle in via a field type.
    Content is stored as raw Markdown; rendering it (e.g. via a Blade
    directive or a CommonMark package the app already depends on) is left to
    the consuming app's own view.
--}}
@php
    $errorKey = "data.{$field->name}";
    $areaClass = 'w-full rounded-lg border bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <textarea rows="8" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        placeholder="Markdown..."
        @disabled($field->isDisabledForAction($action ?? null))
        class="{{ $areaClass }}"></textarea>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
