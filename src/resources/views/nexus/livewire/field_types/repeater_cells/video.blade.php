{{--
    elFinder-picked video inside a repeater row (ShopCategory's `videos`
    gallery) — same cell pattern as repeater_cells/image.blade.php, with the
    same file-type-sniffing preview as the standalone field_types/video.blade.php.
--}}
@php
    $cellId = "field-{$field->name}-{$index}-{$column->name}";
    $value = $row[$column->name] ?? null;
    $normalized = $value ? str_replace('\\', '/', $value) : null;
    $isVideoFile = $normalized && (preg_match('/\.(mp4|webm|ogg)$/i', $normalized)
        || (!str_starts_with($normalized, 'http') && !str_contains($normalized, 'youtube') && !str_contains($normalized, 'vimeo')));
    $errorKey = "relationRows.{$field->name}.{$index}.{$column->name}";
@endphp
<input type="text" class="d-none" id="{{ $cellId }}" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}">

<div class="d-flex align-items-center gap-2">
    @if($isVideoFile)
        <video width="120" height="80" controls>
            <source src="/{{ $normalized }}" type="video/mp4">
        </video>
    @endif
    <button type="button" class="btn btn-sm btn-outline-primary popup_selector" data-inputid="{{ $cellId }}">
        @lang('nexus::translate.choose')
    </button>
</div>

@error($errorKey)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
