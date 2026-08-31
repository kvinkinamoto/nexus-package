{{--
    Livewire Етап 6 — read-only pretty-printed JSON display. ActivityLog's
    `properties` is the only user; ModuleForm::mount() already pre-formats
    the value into $this->data (see its 'json' branch) since a raw
    array/Collection can't bind through wire:model as a scalar.
--}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <textarea rows="6" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        style="font-family: monospace;"
        class="form-control @error($errorKey) is-invalid @enderror"></textarea>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
