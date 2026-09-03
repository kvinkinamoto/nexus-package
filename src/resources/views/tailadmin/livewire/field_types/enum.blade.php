{{--
    Livewire Етап 6 — backed-enum select, mirrors templates/field_types/enum.blade.php's
    option list ($field->enum::cases(), labeled via the same 'nexus::translate.{value}'
    key, falling back to the literal value when untranslated). Not translatable —
    ModuleForm::mount() reads a scalar (enum->value) into data.{name} directly.
--}}
@php
    $errorKey = "data.{$field->name}";
    $moduleName = $module->name ?? 'nexus';
    $cases = $field->enum::cases();
    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        data-choices data-choices-sorting-false
        class="{{ $selectClass }}">
        @foreach($cases as $case)
            <option value="{{ $case->value }}">
                {{-- Prefer the enum's own label() when it declares one (its
                     translation key shape is then the enum's business, not
                     this generic partial's) — falls back to the flat
                     '{module}::translate.{value}' convention otherwise. --}}
                @if(method_exists($case, 'label'))
                    {{ $case->label() }}
                @else
                    @lang($moduleName . '::translate.' . $case->value)
                @endif
            </option>
        @endforeach
    </select>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
