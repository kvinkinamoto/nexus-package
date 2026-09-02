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
<input type="text" class="hidden" id="{{ $cellId }}" wire:model="relationRows.{{ $field->name }}.{{ $index }}.{{ $column->name }}">

<div class="flex items-center gap-2">
    @if($isVideoFile)
        <video width="120" height="80" controls class="rounded-md">
            <source src="/{{ $normalized }}" type="video/mp4">
        </video>
    @endif
    <button type="button" class="popup_selector rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/5" data-inputid="{{ $cellId }}">
        @lang('nexus::translate.choose')
    </button>
</div>

@error($errorKey)
    <p class="mt-1.5 text-xs text-error-500">{{ $message }}</p>
@enderror
