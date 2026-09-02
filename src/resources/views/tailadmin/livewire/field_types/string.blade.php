{{--
    Livewire Етап 6: translatable-aware. Unlike the legacy per-section
    language-tab switcher (templates/sections/base.blade.php's
    'sectionFieldsTranslates...' block), each locale renders as its own
    labeled row stacked inside this one field block — simpler to implement
    correctly than replicating the cross-field tab switcher, and every
    locale is still always visible/editable in one render (no extra round
    trip to switch tabs). $this->activeLocales() mirrors
    FormBuilder::getLanguages() (published+enabled languages, or just the
    app locale if the Language module itself is disabled).
--}}
@php
    $errorKey = "data.{$field->name}";
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    @if($field->isTranslate)
        @foreach($this->activeLocales() as $locale)
            @php $localeErrorKey = "{$errorKey}.{$locale}"; @endphp
            <div class="mb-1.5 flex items-center gap-2">
                <span class="flex h-11 w-14 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs font-semibold uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">{{ $locale }}</span>
                <input type="text" wire:model="data.{{ $field->name }}.{{ $locale }}"
                    @disabled($field->isDisabledForAction($action ?? null))
                    class="{{ $inputClass }}">
            </div>
            @error($localeErrorKey)
                <p class="mb-1.5 text-xs text-error-500">{{ $message }}</p>
            @enderror
        @endforeach
    @else
        {{--
            No HTML5 `required` here on purpose: FieldConfigDto->isRequired
            defaults to true regardless of what the module's actual Request/
            NexusRuleCollector rules say (Cache's own request_data is
            isRequired=true on the DTO but nullable in AdminStoreRequest) — a
            blanket `required` attribute silently blocks the browser's native
            form submission before wire:submit ever fires, with no visible
            error. $this->rules() (ModuleForm::rules()) is the authoritative
            source and already drives real server-side validation.
        --}}
        <input type="text" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="{{ $inputClass }}">

        @error($errorKey)
            <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
        @enderror
    @endif
</div>
