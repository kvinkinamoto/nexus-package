{{--
    Backed-enum select, mirrors templates/field_types/enum.blade.php's option
    list ($field->enum::cases(), labeled via the same 'nexus::translate.{value}'
    key, falling back to the literal value when untranslated). Not translatable —
    ModuleForm::mount() reads a scalar (enum->value) into data.{name} directly.
    Not nullable — a backed enum field always carries a default value.

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $moduleName = $module->name ?? 'nexus';
    $options = collect($field->enum::cases())
        ->map(fn ($case) => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : __($moduleName . '::translate.' . $case->value),
        ])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options, 'nullable' => false])
