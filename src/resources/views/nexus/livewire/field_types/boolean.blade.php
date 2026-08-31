{{-- Livewire Етап 6 — boolean switch. Not translatable; a boolean has no locale variants. --}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    <div class="form-check form-switch">
        <input type="checkbox" role="switch" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="form-check-input @error($errorKey) is-invalid @enderror">
        <label for="field-{{ $field->name }}" class="form-check-label">
            @if(str_contains($field->label ?? '', '::'))
                @lang($field->label)
            @else
                @lang(Str::lcfirst($module->name) . '::translate.' . Str::lower($field->label ?? $field->name))
            @endif
        </label>
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
