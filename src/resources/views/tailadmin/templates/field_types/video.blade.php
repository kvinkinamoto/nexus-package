@php
    $inputId = $field->name . '_' . ($tab_lang ?? '');
    $val = isset($tab_lang)
        ? (isset($model) ? old($field->name . '.' . $tab_lang, $model->getTranslation($field->name, $tab_lang, false)) : old($field->name . '.' . $tab_lang))
        : (isset($model) ? old($field->name, $model->{$field->name}) : old($field->name));
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($field->name . '.' . ($tab_lang ?? '')) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700');
@endphp
<div class="mb-4">
    <label for="{{$field->name}}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
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

    <div id="{{$inputId}}_preview_container" class="mb-2">
        @if($val)
            @php
                $val = str_replace('\\', '/', $val);
            @endphp

            @if(preg_match('/\.(mp4|webm|ogg)$/i', $val) || (!str_starts_with($val, 'http') && !str_contains($val, 'youtube') && !str_contains($val, 'vimeo')))
                <video id="{{$inputId}}_prev" width="320" height="240" controls class="w-full rounded-lg">
                    <source src="/{{$val}}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            @elseif(preg_match('/\.(m4a|mp3|ogg)$/i', $val))
                <div>
                    <img src="/img/audio-placeholder.png" class="mb-2 w-full rounded-lg">
                    <audio id="{{$inputId}}_prev" controls class="w-full" src="/{{$val}}"></audio>
                </div>
            @elseif(str_contains($val, 'youtube') || str_contains($val, 'vimeo'))
                <iframe id="{{$inputId}}_prev" width="320" height="240"
                    src="{{\Str::contains($val, 'youtube') ? 'https://www.youtube.com/embed/' . last(explode('v=', $val)) : 'https://player.vimeo.com/video/' . last(explode('/', $val))}}"
                    frameborder="0" allowfullscreen class="mb-2 w-full rounded-lg"></iframe>
            @endif
        @endif
    </div>

    <div class="flex items-center gap-2">
        @if($field->isDisabledForAction($action ?? null))
            <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$val}}">
        @endif
        <button data-inputid="{{$inputId}}" type="button"
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            class="popup_selector rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">@lang('nexus::translate.choose')</button>
        <input type="text" id="{{$inputId}}" @if(isset($tab_lang))
        name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$val}}"
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            class="{{ $inputClass }}">
    </div>
    {!! $errors->first($field->name . '.' . ($tab_lang ?? ''), '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>

@push('js')
    <script>
        (function () {
            const input = document.getElementById('{{$inputId}}');
            const previewContainer = document.getElementById('{{$inputId}}_preview_container');
            if (!input || !previewContainer) return;

            function getYoutubeEmbedUrl(url) {
                if (!url) return '';
                let videoId = '';
                const watchMatch = url.match(/v=([a-zA-Z0-9_-]+)/);
                if (watchMatch) videoId = watchMatch[1];
                const shortMatch = url.match(/youtu\.be\/([a-zA-Z0-9_-]+)/);
                if (shortMatch) videoId = shortMatch[1];
                const embedMatch = url.match(/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/);
                if (embedMatch) videoId = embedMatch[1];
                return videoId ? 'https://www.youtube.com/embed/' + videoId : '';
            }

            function updatePreview(val) {
                if (!val) { previewContainer.innerHTML = ''; return; }
                val = val.replaceAll('\\', '/');

                let html = '';
                if (/\.(mp4|webm|ogg)$/i.test(val) || (!val.startsWith('http') && !val.includes('youtube') && !val.includes('vimeo'))) {
                    html = `<video id="{{$inputId}}_prev" width="320" height="240" controls class="w-full rounded-lg">
                            <source src="/${val}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>`;
                } else if (/\.(m4a|mp3|ogg)$/i.test(val)) {
                    html = `<div>
                            <img src="/img/audio-placeholder.png" class="mb-2 w-full rounded-lg">
                            <audio id="{{$inputId}}_prev" controls class="w-full" src="/${val}"></audio>
                        </div>`;
                } else if (val.includes('youtube') || val.includes('youtu.be') || val.includes('vimeo')) {
                    let src = '';
                    if (val.includes('youtube') || val.includes('youtu.be')) {
                        src = getYoutubeEmbedUrl(val);
                    } else if (val.includes('vimeo')) {
                        const v = val.split('/').pop();
                        src = 'https://player.vimeo.com/video/' + v;
                    }
                    html = `<iframe id="{{$inputId}}_prev" width="320" height="240"
                            src="${src}" frameborder="0" allowfullscreen class="mb-2 w-full rounded-lg"></iframe>`;
                }

                previewContainer.innerHTML = html;
            }

            updatePreview(input.value);
            input.addEventListener('input', function () { updatePreview(input.value); });
        })();
    </script>
@endpush
