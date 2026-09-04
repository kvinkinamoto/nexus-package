{{--
    Shared radio-button group for any single-value field wanting a
    fixed-choice list visible all at once, instead of _native_select's
    dropdown — port of templates/field_types/select_enum_data_radio.blade.php's
    intent (radio-rendered choices), rebuilt against this pipeline's own
    conventions rather than that partial's ad-hoc $field->default-as-options
    shape.

    Expects: $field (FieldConfigDto), $options (array of ['value'=>, 'label'=>]).
--}}
@php
    $errorKey = "data.{$field->name}";
    $selectedValue = $this->data[$field->name] ?? null;
    $isDisabled = $field->isDisabledForAction($action ?? null);
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <div class="flex flex-wrap gap-4">
        @foreach($options as $option)
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300 {{ $isDisabled ? 'cursor-not-allowed opacity-50' : '' }}">
                <input type="radio" wire:model="data.{{ $field->name }}" value="{{ $option['value'] }}"
                    @disabled($isDisabled)
                    class="h-4 w-4 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                {{ $option['label'] }}
            </label>
        @endforeach
    </div>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
