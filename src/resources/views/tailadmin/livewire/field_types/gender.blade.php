{{--
    Port of User's own field_types/gender.blade.php. Not folded into the
    generic 'enum' field type — see ModuleForm::mount()'s 'gender' branch for
    why (Gender::label()'s translation key shape doesn't match enum.blade.php's
    convention).

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $options = collect(\App\Nexus\Modules\User\Enums\Gender::all())
        ->map(fn ($option) => ['value' => $option['value'], 'label' => $option['label']])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options, 'placeholder' => '—'])
