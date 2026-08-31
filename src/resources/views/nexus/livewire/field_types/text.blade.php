{{-- Livewire Етап 6 — textarea counterpart of string.blade.php; same translatable-locale-rows approach. --}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    @if($field->isTranslate)
        @foreach($this->activeLocales() as $locale)
            @php $localeErrorKey = "{$errorKey}.{$locale}"; @endphp
            <div class="mb-1">
                <div class="input-group-text text-uppercase d-inline-block mb-1" style="width: 3.5rem;">{{ $locale }}</div>
                <textarea rows="4" wire:model="data.{{ $field->name }}.{{ $locale }}"
                    @disabled($field->isDisabledForAction($action ?? null))
                    class="form-control @error($localeErrorKey) is-invalid @enderror"></textarea>
            </div>
            @error($localeErrorKey)
                <div class="invalid-feedback d-block mb-1">{{ $message }}</div>
            @enderror
        @endforeach
    @else
        <textarea rows="4" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="form-control @error($errorKey) is-invalid @enderror"></textarea>

        @error($errorKey)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @endif
</div>
