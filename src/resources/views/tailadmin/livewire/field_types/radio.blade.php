{{--
    Options come from the same two sources select/enum already use: a
    backed enum ($field->enum) if set, otherwise $field->customData (see
    select.blade.php's own docblock on how that gets populated) — so an
    existing enum or select field can switch to radio rendering just by
    changing its #[Field(type:)] string, no other config change needed.
--}}
@php
    $moduleName = $module->name ?? 'nexus';

    if ($field->enum ?? null) {
        $options = collect($field->enum::cases())
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : __($moduleName . '::translate.' . $case->value),
            ])
            ->all();
    } else {
        $options = collect($field->customData ?? [])
            ->map(fn ($option) => ['value' => $option['value'], 'label' => $option['name']])
            ->all();
    }
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_radio', ['field' => $field, 'options' => $options])
