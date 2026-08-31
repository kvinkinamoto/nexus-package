{{-- Livewire Етап 6 — number input, same shape as string.blade.php. --}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <input type="number" step="any" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
