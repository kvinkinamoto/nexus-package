{{--
    elFinder-picked image inside a repeater row (ShopProduct's `images`
    gallery — see StoreRelationActionMethod::extraRelationScopeAttributes()
    for how a saved row still ends up correctly scoped to this specific
    morphMany relation). Same text/d-none + popup_selector pattern as the
    standalone field_types/image.blade.php, just keyed per row so each
    row's elFinder button targets only its own cell.
--}}
@php
    $cellId = "field-{$field->name}-{$index}-{$column->name}";
    $value = $row[$column->name] ?? null;
    $errorKey = "relationRows.{$field->name}.{$index}.{$column->name}";
@endphp
<input type="text" class="d-none" id="{{ $cellId }}" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}">

<div class="d-flex align-items-center gap-2">
    <div class="bg-light rounded d-flex align-items-center justify-content-center flex-shrink-0"
         style="width: 56px; height: 56px; overflow: hidden;">
        <img src="/{{ ltrim($value ?? 'nexus/images/no-image.jpg', '/') }}"
             class="img-fluid rounded" style="max-width: 56px; max-height: 56px; object-fit: contain;">
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary popup_selector" data-inputid="{{ $cellId }}">
        @lang('nexus::translate.chooseImage')
    </button>
</div>

@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
