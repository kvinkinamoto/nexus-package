{{--
    #[Field(type: 'slug', slugSource: 'title')] — the "generate" button calls
    Livewire\ModuleForm::generateSlug(), which runs Str::slug() server-side
    (not a JS reimplementation, so it can never drift from Laravel's own
    slugify rules). Plain text input otherwise — a slug is still directly
    editable, generate is a convenience, not the only way to set it.
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="flex items-center gap-2">
        <input type="text" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="{{ $inputClass }}">

        @if($field->slugSource ?? null)
            <button type="button" wire:click="generateSlug('{{ $field->name }}')"
                @disabled($field->isDisabledForAction($action ?? null))
                class="shrink-0 rounded-lg border border-gray-300 px-3 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">
                @lang('nexus::translate.generate')
            </button>
        @endif
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
