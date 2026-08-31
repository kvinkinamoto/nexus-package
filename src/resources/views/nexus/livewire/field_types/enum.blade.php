{{--
    Livewire Етап 6 — backed-enum select, mirrors templates/field_types/enum.blade.php's
    option list ($field->enum::cases(), labeled via the same 'nexus::translate.{value}'
    key, falling back to the literal value when untranslated). Not translatable —
    ModuleForm::mount() reads a scalar (enum->value) into data.{name} directly.
--}}
@php
    $errorKey = "data.{$field->name}";
    $moduleName = $module->name ?? 'nexus';
    $cases = $field->enum::cases();
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        @foreach($cases as $case)
            <option value="{{ $case->value }}">
                {{-- Prefer the enum's own label() when it declares one (its
                     translation key shape is then the enum's business, not
                     this generic partial's) — falls back to the flat
                     '{module}::translate.{value}' convention otherwise. --}}
                @if(method_exists($case, 'label'))
                    {{ $case->label() }}
                @else
                    @lang($moduleName . '::translate.' . $case->value)
                @endif
            </option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
