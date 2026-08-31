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
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <select id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}"
        @disabled($field->isDisabledForAction($action ?? null))
        class="form-control @error($errorKey) is-invalid @enderror">
        <option value="">@lang('nexus::translate.chooseRelation')</option>
        @foreach($cases as $case)
            <option value="{{ $case->modelClass() }}">{{ $case->label() }}</option>
        @endforeach
    </select>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
