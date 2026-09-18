{{--
    elFinder-picked image inside a repeater row (ShopProduct's `images`
    gallery — see StoreRelationActionMethod::extraRelationScopeAttributes()
    for how a saved row still ends up correctly scoped to this specific
    morphMany relation). Same text/hidden + popup_selector pattern as the
    standalone field_types/image.blade.php, just keyed per row so each
    row's elFinder button targets only its own cell.
--}}
@php
    $cellId = "field-{$field->name}-{$index}-{$column->name}";
    $value = $row[$column->name] ?? null;
    $errorKey = "relationRows.{$field->name}.{$index}.{$column->name}";
@endphp
<input type="text" class="hidden" id="{{ $cellId }}" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}">

<div class="flex items-center gap-2">
    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
        <img src="/{{ ltrim($value ?? 'nexus/images/no-image.jpg', '/') }}"
             class="max-h-14 max-w-14 object-contain">
    </div>
    <button type="button" class="popup_selector rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" data-inputid="{{ $cellId }}">
        @lang('nexus::translate.chooseImage')
    </button>
</div>

@error($errorKey)
    <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
@enderror
