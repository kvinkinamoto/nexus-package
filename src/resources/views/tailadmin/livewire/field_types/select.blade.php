{{--
    Options come from $field->customData ([['value'=>..., 'name'=>...], ...]),
    populated by an AdminFormBuilding listener (see Widget's
    PopulateWidgetSelectOptions) — ModuleForm::resolveModuleConfig() dispatches
    that event and caches the resulting DTO for the rest of this round-trip,
    so customData set there is still present by the time this partial renders.

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $options = collect($field->customData ?? [])
        ->map(fn ($option) => ['value' => $option['value'], 'label' => $option['name']])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options])
