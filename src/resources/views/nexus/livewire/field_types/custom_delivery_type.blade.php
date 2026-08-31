{{-- Livewire Етап 6 — port of Order's field_types/custom_delivery_type.blade.php. Independent of the delivery_method select (not filtered by it) — the legacy version isn't either. --}}
@php
    $errorKey = "data.{$field->name}";
    $types = \App\Nexus\Modules\DeliveryType\Models\DeliveryType::query()->with('delivery')->orderBy('key')->get();
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        <option value="">@lang('nexus::translate.chooseRelation')</option>
        @foreach($types as $type)
            <option value="{{ $type->key }}">{{ $type->delivery?->title }} &mdash; {{ $type->title }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
