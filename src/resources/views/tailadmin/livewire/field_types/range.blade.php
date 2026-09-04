{{--
    $wire.entangle() (not wire:model.live) so dragging the slider updates the
    displayed number on every tick via Alpine, without a server round-trip
    per tick — only syncs to Livewire (and so $wire's own value) on release,
    same as a plain wire:model input's default 'change' timing.
--}}
@php
    $errorKey = "data.{$field->name}";
    $customData = $field->customData ?? [];
    $min = $customData['min'] ?? 0;
    $max = $customData['max'] ?? 100;
    $step = $customData['step'] ?? 1;
@endphp
<div class="mb-4" x-data="{ val: $wire.entangle('data.{{ $field->name }}') }">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="flex items-center gap-3">
        <input type="range" id="field-{{ $field->name }}" x-model.number="val"
            min="{{ $min }}" max="{{ $max }}" step="{{ $step }}"
            @disabled($field->isDisabledForAction($action ?? null))
            class="h-2 w-full cursor-pointer accent-brand-500 disabled:cursor-not-allowed disabled:opacity-50">
        <span class="w-12 shrink-0 text-right text-sm font-medium text-gray-700 dark:text-gray-300" x-text="val"></span>
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
