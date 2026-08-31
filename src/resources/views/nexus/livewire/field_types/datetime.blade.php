{{--
    Livewire Етап 6 — HTML5 datetime-local input, replacing the legacy
    flatpickr text input. Same "Y-m-d\TH:i" shape both ways (see
    ModuleForm::mount()'s datetime branch) — no JS date-picker library needed.
--}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <input type="datetime-local" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
