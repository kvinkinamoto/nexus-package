{{-- Livewire Етап 6 — port of Order's field_types/custom_payment_method.blade.php. Plain select over all PaymentMethod rows, keyed by their string `code` column. --}}
@php
    $errorKey = "data.{$field->name}";
    $methods = \App\Nexus\Modules\PaymentMethod\Models\PaymentMethod::query()->orderBy('code')->get();
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        <option value="">@lang('nexus::translate.chooseRelation')</option>
        @foreach($methods as $method)
            <option value="{{ $method->code }}">{{ $method->title }} ({{ $method->code }})</option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
