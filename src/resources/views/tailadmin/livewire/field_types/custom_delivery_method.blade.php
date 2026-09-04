{{--
    Port of Order's field_types/custom_delivery_method.blade.php. Plain
    select over all Delivery rows, keyed by their string `key` column, not id.

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $options = \App\Nexus\Modules\Delivery\Models\Delivery::query()->orderBy('key')->get()
        ->map(fn ($delivery) => ['value' => $delivery->key, 'label' => $delivery->title])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options, 'placeholder' => __('nexus::translate.chooseRelation')])
