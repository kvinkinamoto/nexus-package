{{--
    Livewire Етап 6 — port of templates/field_types/video.blade.php. Same
    elFinder-popup mechanism as field_types/image.blade.php (a plain string
    column here, not a polymorphic relation — ShopProduct's `video` is in its
    own $fillable) — the preview is reactive Blade instead of the legacy's
    jQuery 'input'-event listener, since Livewire already re-renders this
    block whenever data.{name} changes, no extra JS needed for that part.
--}}
@php
    $errorKey = "data.{$field->name}";
    $value = $this->data[$field->name] ?? null;
    $normalized = $value ? str_replace('\\', '/', $value) : null;
    $isVideoFile = $normalized && (preg_match('/\.(mp4|webm|ogg)$/i', $normalized)
        || (!str_starts_with($normalized, 'http') && !str_contains($normalized, 'youtube') && !str_contains($normalized, 'vimeo')));
    $isAudioFile = $normalized && preg_match('/\.(m4a|mp3|ogg)$/i', $normalized);
    $isEmbed = $normalized && (str_contains($normalized, 'youtube') || str_contains($normalized, 'vimeo'));
    $embedSrc = $isEmbed
        ? (str_contains($normalized, 'youtube') ? 'https://www.youtube.com/embed/' . last(explode('v=', $normalized)) : 'https://player.vimeo.com/video/' . last(explode('/', $normalized)))
        : null;
@endphp
<div class="mb-3">
    @include('nexus::' . config('nexus.template') . '.livewire.field_types._label', ['field' => $field])

    <input type="text" class="d-none" id="field-{{ $field->name }}" wire:model="data.{{ $field->name }}">

    <div class="mb-2">
        @if($isVideoFile)
            <video width="320" height="240" controls class="video-fluid col-12">
                <source src="/{{ $normalized }}" type="video/mp4">
            </video>
        @elseif($isAudioFile)
            <audio controls class="col-12" src="/{{ $normalized }}"></audio>
        @elseif($isEmbed)
            <iframe width="320" height="240" src="{{ $embedSrc }}" frameborder="0" allowfullscreen class="col-12 mb-2"></iframe>
        @endif
    </div>

    <div class="input-group mb-3">
        <button type="button" class="btn btn-primary popup_selector" data-inputid="field-{{ $field->name }}"
            @disabled($field->isDisabledForAction($action ?? null))>
            @lang('nexus::translate.choose')
        </button>
        <span class="form-control text-truncate">{{ $normalized ?? '' }}</span>
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
