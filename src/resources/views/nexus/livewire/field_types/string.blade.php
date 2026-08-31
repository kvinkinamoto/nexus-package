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
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    @if($field->isTranslate)
        @foreach($this->activeLocales() as $locale)
            @php $localeErrorKey = "{$errorKey}.{$locale}"; @endphp
            <div class="input-group mb-1">
                <span class="input-group-text text-uppercase" style="width: 3.5rem;">{{ $locale }}</span>
                <input type="text" wire:model="data.{{ $field->name }}.{{ $locale }}"
                    @disabled($field->isDisabledForAction($action ?? null))
                    class="form-control @error($localeErrorKey) is-invalid @enderror">
            </div>
            @error($localeErrorKey)
                <div class="invalid-feedback d-block mb-1">{{ $message }}</div>
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
            class="form-control @error($errorKey) is-invalid @enderror">

        @error($errorKey)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @endif
</div>
