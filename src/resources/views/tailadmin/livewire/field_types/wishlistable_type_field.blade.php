{{--
    Livewire Етап 6 — port of Wishlist's bespoke field_types/wishlistable_type_field.blade.php.
    A fixed, tiny select backed by WishlistableTypeEnum: the DB column stores
    the morphed model's FQCN (->modelClass()), not the enum's own ->value, so
    this can't reuse field_types/enum.blade.php as-is (that one submits
    $case->value directly) — kept as its own small partial instead.
--}}
@php
    $errorKey = "data.{$field->name}";
    $cases = \App\Nexus\Modules\Wishlist\Enums\WishlistableTypeEnum::cases();
    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorKey) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
@endphp
<div class="mb-4">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        data-choices data-choices-sorting-false
        class="{{ $selectClass }}">
        <option value="">@lang('nexus::translate.chooseRelation')</option>
        @foreach($cases as $case)
            <option value="{{ $case->modelClass() }}">{{ $case->label() }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
    @enderror
</div>
