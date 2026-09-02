@php
    $value = $item->{$fieldName};
    if ($value instanceof \Illuminate\Support\Collection) {
        $first = $value->first();
        if ($first === null) {
            $src = $href = '';
        } elseif (is_array($first)) {
            $src = $href = $first['path'] ?? '';
        } else {
            $src = $href = $first->path ?? '';
        }
    } elseif (is_object($value)) {
        $src = $value->path ?? '';
        $href = $value->path ?? '';
    } elseif (is_array($value) && isset($value['path'])) {
        $src = $value['path'];
        $href = $value['path'];
    } else {
        $src = $href = $value;
    }
@endphp
<div class="flex h-15 w-15 items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
    <a href="{{ $href }}" target="_blank" rel="noopener">
        <img src="/{{ $src }}" alt="" class="max-h-15 max-w-15 object-contain">
    </a>
</div>
