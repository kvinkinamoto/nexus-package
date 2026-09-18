{{--
    Port of Wishlist's bespoke field_types/wishlistable_type_field.blade.php.
    A fixed, tiny select backed by WishlistableTypeEnum: the DB column stores
    the morphed model's FQCN (->modelClass()), not the enum's own ->value, so
    this can't reuse field_types/enum.blade.php as-is (that one submits
    $case->value directly) — kept as its own small partial instead.

    Rendered by the shared _native_select partial (see its own docblock for
    why — this used to be a Choices.js-wrapped <select>).
--}}
@php
    $options = collect(\App\Nexus\Modules\Wishlist\Enums\WishlistableTypeEnum::cases())
        ->map(fn ($case) => ['value' => $case->modelClass(), 'label' => $case->label()])
        ->all();
@endphp
@include('nexus::' . config('nexus.template') . '.livewire.field_types._native_select', ['field' => $field, 'options' => $options, 'placeholder' => __('nexus::translate.chooseRelation')])
