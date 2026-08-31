@php
//    dd($item->{$fieldName}, $fieldName);
    $value = $item->{$fieldName};
    if ($value instanceof \Illuminate\Support\Collection) {
        // A hasMany/morphMany-backed field (e.g. #[Relation(type: 'hasMany')])
        // resolves to a Collection — empty when the record has no images
        // yet, in which case Collection::__get() would throw on ->path
        // rather than returning null, so it can't be treated as an object.
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
<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; overflow: hidden;">
    <a href="{{ $href }}" class="image-popup">
        <img src="/{{ $src }}" alt="" style="max-width: 60px; max-height: 60px; object-fit: contain;">
    </a>
</div>
