{{--
    Livewire Етап 6 — port of templates/field_types/phone.blade.php. Same
    shape as string.blade.php with an icon; deliberately skips porting the
    legacy version's IMask.js input mask — that library initializes once via
    a DOMContentLoaded listener and has no hook to re-attach itself after a
    Livewire morph, so it would only ever mask on the very first paint and
    silently stop working after any reactive update. A plain text input with
    the same server-side format validation (see AdminUpdateRequest's phone
    regex rule) is honest about that gap rather than shipping a mask that
    looks like it works and then doesn't.
--}}
@php
    $errorKey = "data.{$field->name}";
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="input-group mb-3">
        <span class="input-group-text fs-20">
            <i class="{{ nexus_icon('phone') }} fs-20"></i>
        </span>
        <input type="text" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="form-control @error($errorKey) is-invalid @enderror">
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
