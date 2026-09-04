{{--
    Port of Order's field_types/custom_payment_method.blade.php. Plain select
    over all PaymentMethod rows, keyed by their string `code` column.

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $options = \App\Nexus\Modules\PaymentMethod\Models\PaymentMethod::query()->orderBy('code')->get()
        ->map(fn ($method) => ['value' => $method->code, 'label' => "{$method->title} ({$method->code})"])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options, 'placeholder' => __('nexus::translate.chooseRelation')])
