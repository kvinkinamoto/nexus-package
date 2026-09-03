{{--
    Livewire Етап 6 — port of User's own field_types/gender.blade.php.
    Not folded into the generic 'enum' field type — see
    ModuleForm::mount()'s 'gender' branch for why (Gender::label()'s
    translation key shape doesn't match enum.blade.php's convention).
--}}
@php
    $errorKey = "data.{$field->name}";
    $options = \App\Nexus\Modules\User\Enums\Gender::all();
    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        data-choices data-choices-sorting-false
        class="{{ $selectClass }}">
        <option value="">&mdash;</option>
        @foreach($options as $option)
            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
