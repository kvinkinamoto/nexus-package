{{--
    Livewire Етап 6 — port of User's own field_types/gender.blade.php.
    Not folded into the generic 'enum' field type — see
    ModuleForm::mount()'s 'gender' branch for why (Gender::label()'s
    translation key shape doesn't match enum.blade.php's convention).
--}}
@php
    $errorKey = "data.{$field->name}";
    $options = \App\Nexus\Modules\User\Enums\Gender::all();
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        <option value="">&mdash;</option>
        @foreach($options as $option)
            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
