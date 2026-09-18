{{--
    Port of Order's field_types/custom_delivery_type.blade.php. Independent of
    the delivery_method select (not filtered by it) — the legacy version
    isn't either.

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $options = \App\Nexus\Modules\DeliveryType\Models\DeliveryType::query()->with('delivery')->orderBy('key')->get()
        ->map(fn ($type) => ['value' => $type->key, 'label' => trim(($type->delivery?->title ?? '') . ' — ' . $type->title)])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options, 'placeholder' => __('nexus::translate.chooseRelation')])
