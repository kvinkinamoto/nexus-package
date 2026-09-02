{{-- Livewire Етап 6 — port of Order's field_types/custom_delivery_type.blade.php. Independent of the delivery_method select (not filtered by it) — the legacy version isn't either. --}}
@php
    $errorKey = "data.{$field->name}";
    $types = \App\Nexus\Modules\DeliveryType\Models\DeliveryType::query()->with('delivery')->orderBy('key')->get();
    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="{{ $selectClass }}">
        <option value="">@lang('nexus::translate.chooseRelation')</option>
        @foreach($types as $type)
            <option value="{{ $type->key }}">{{ $type->delivery?->title }} &mdash; {{ $type->title }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
