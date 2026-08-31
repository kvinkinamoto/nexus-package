{{-- Livewire Етап 6 — port of templates/field_types/email.blade.php. Same shape as string.blade.php, just type="email" with an icon. --}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="input-group mb-3">
        <span class="input-group-text fs-20">
            <i class="{{ nexus_icon('email') }} fs-18"></i>
        </span>
        <input type="email" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="form-control @error($errorKey) is-invalid @enderror">
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
