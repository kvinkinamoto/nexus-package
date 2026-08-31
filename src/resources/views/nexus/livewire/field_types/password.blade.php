{{--
    Livewire Етап 6 — port of templates/field_types/password.blade.php.
    Always renders empty (see ModuleForm::mount()'s 'password' branch) —
    on edit this is a "set a new password" field, not a display of the
    current one; leaving it blank and saving leaves the password unchanged
    (User's AdminUpdateRequest strips an empty password before validation).
--}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <input type="password" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        autocomplete="new-password"
        class="form-control @error($errorKey) is-invalid @enderror">

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
