{{-- Livewire Етап 6 — port of Order's field_types/custom_delivery_method.blade.php. Plain select over all Delivery rows, keyed by their string `key` column, not id. --}}
@php
    $errorKey = "data.{$field->name}";
    $deliveries = \App\Nexus\Modules\Delivery\Models\Delivery::query()->orderBy('key')->get();
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        <option value="">@lang('nexus::translate.chooseRelation')</option>
        @foreach($deliveries as $delivery)
            <option value="{{ $delivery->key }}">{{ $delivery->title }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
