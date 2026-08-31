<div class="form-group">
    <label for="{{$field->name}}" class="form-label">
        @if(isset($field->label) && !empty($field->label))
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->label)
            @endif
        @else
            @if(str_contains($field->name, '::'))
                @lang($field->name)
            @else
                @lang(lcfirst($module->name) . '::translate.' . $field->name)
            @endif {{$tab_lang ?? ''}}
        @endif
    </label>
    <div class="input-group mb-3">
        <?php
$value = $value ?? '';
$val = isset($tab_lang)
    ? (isset($model) ? old($field->name . '.' . $tab_lang, $model->getTranslation($field->name, $tab_lang, false)) : old($field->name . '.' . $tab_lang))
    : (isset($model) ? old($field->name, $model->{$field->name}) : old($field->name));
        ?>

        <div id="{{$field->name . '_' . ($tab_lang ?? '')}}_preview_container" class="mb-2 col-12">
            @if($val)
                @php
                    $val = str_replace('\\', '/', $val);
                @endphp

                @if(preg_match('/\.(mp4|webm|ogg)$/i', $val) || (!str_starts_with($val, 'http') && !str_contains($val, 'youtube') && !str_contains($val, 'vimeo')))
                    <video id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev" width="320" height="240" controls
                        class="video-fluid col-12">
                        <source src="/{{$val}}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                @elseif(preg_match('/\.(m4a|mp3|ogg)$/i', $val))
                    <div>
                        <img src="/img/audio-placeholder.png" class="img-fluid mb-2">
                        <audio id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev" controls class="col-12" src="/{{$val}}"></audio>
                    </div>
                @elseif(str_contains($val, 'youtube') || str_contains($val, 'vimeo'))
                    <iframe id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev" width="320" height="240"
                        src="{{\Str::contains($val, 'youtube') ? 'https://www.youtube.com/embed/' . last(explode('v=', $val)) : 'https://player.vimeo.com/video/' . last(explode('/', $val))}}"
                        frameborder="0" allowfullscreen class="col-12 mb-2"></iframe>
                @endif
            @endif
        </div>

        <div class="input-group-prepend">
            @if($field->isDisabledForAction($action ?? null))
                <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$val}}">
            @endif
            <button data-inputid="{{$field->name . '_' . ($tab_lang ?? '')}}" type="button"
                @if($field->isDisabledForAction($action ?? null)) disabled @endif
                class="btn btn-primary popup_selector">@lang('nexus::translate.choose')</button>
        </div>
        <input type="text" id="{{$field->name . '_' . ($tab_lang ?? '')}}" @if(isset($tab_lang))
        name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$val}}"
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            class="form-control @error($field->name . '.' . ($tab_lang ?? '')) is-invalid @enderror">
        {!! $errors->first($field->name . '.' . ($tab_lang ?? ''), '<small class="error invalid-feedback">:message</small>') !!}
    </div>
</div>

@section('js')
    @parent
    <script>
        jQuery(document).ready(function () {
            const input = jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}');
            const previewContainer = jQuery('#{{$field->name . '_' . ($tab_lang ?? '')}}_preview_container');

            function updatePreview(val) {
                if (!val) return previewContainer.html('');
                val = val.replaceAll('\\', '/');

                let html = '';
                if (/\.(mp4|webm|ogg)$/i.test(val) || (!val.startsWith('http') && !val.includes('youtube') && !val.includes('vimeo'))) {
                    html = `<video id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev" width="320" height="240" controls class="video-fluid col-12">
                            <source src="/${val}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>`;
                } else if (/\.(m4a|mp3|ogg)$/i.test(val)) {
                    html = `<div>
                            <img src="/img/audio-placeholder.png" class="img-fluid mb-2">
                            <audio id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev" controls class="col-12" src="/${val}"></audio>
                        </div>`;
                } else if (val.includes('youtube') || val.includes('youtu.be') || val.includes('vimeo')) {
                    let src = '';
                    if (val.includes('youtube') || val.includes('youtu.be')) {
                        src = getYoutubeEmbedUrl(val);
                    } else if (val.includes('vimeo')) {
                        const v = val.split('/').pop();
                        src = 'https://player.vimeo.com/video/' + v;
                    }
                    html = `<iframe id="{{$field->name . '_' . ($tab_lang ?? '')}}_prev" width="320" height="240"
                            src="${src}" frameborder="0" allowfullscreen class="col-12 mb-2"></iframe>`;
                }

                previewContainer.html(html);
            }

            function getYoutubeEmbedUrl(url) {
                if (!url) return '';
                let videoId = '';

                // 1. full URL youtube.com/watch?v=...
                const watchMatch = url.match(/v=([a-zA-Z0-9_-]+)/);
                if (watchMatch) videoId = watchMatch[1];

                // 2. short URL youtu.be/...
                const shortMatch = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
                if (shortMatch) videoId = shortMatch[1];

                // 3. already embed? (https://www.youtube.com/embed/VIDEO_ID)
                const embedMatch = url.match(/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/);
                if (embedMatch) videoId = embedMatch[1];

                return videoId ? 'https://www.youtube.com/embed/' + videoId : '';
            }

            // Встановлюємо початкове значення
            updatePreview(input.val());

            // Слухаємо зміни input
            input.on("propertychange change click keyup input paste", function () {
                updatePreview(input.val());
            });
        });
    </script>
@endsection