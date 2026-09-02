@php
    $value = '';
    if (isset($tab_lang)) {
        if (isset($model)) {
            // Handle translated fields; for image use relationLoaded to get path
            if ($field->name === 'image' && $model->relationLoaded('image') && $model->image) {
                $value = old($field->name . '.' . $tab_lang, $model->image->path ?? null);
            } else {
                $value = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
            }
        } else {
            $value = old($field->name . '.' . $tab_lang, $field->defaultValue ?? null);
        }
    } else {
        if (isset($model)) {
            if ($field->name === 'image' && $model->relationLoaded('image') && $model->image) {
                $value = old($field->name, $model->image->path ?? null);
            } else {
                $value = old($field->name, ($model->{$field->name}));
            }
        } else {
            $value = old($field->name, $field->defaultValue ?? null);
        }
    }
    if (!isset($value)) {
        $value = $field->defaultValue ?? null;
    }

    $inputId = $field->name . '_' . ($tab_lang ?? '');
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
        @if($field->isRequired ?? false)
            *
        @endif
    </label>
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$value}}">
    @endif
    <input type="text" id="{{$inputId}}" @if(isset($tab_lang))
    name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{$value}}"
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        class="{{ $inputClass }}">
    {!! $errors->first($field->name . '.' . ($tab_lang ?? ''), '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>

<button type="button" data-inputid="{{$inputId}}"
    @if($field->isDisabledForAction($action ?? null)) disabled @endif
    class="popup_selector mb-3 flex w-full flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center hover:border-brand-300 hover:bg-brand-50/50 disabled:pointer-events-none disabled:opacity-60 dark:border-gray-700 dark:hover:border-brand-700 dark:hover:bg-brand-500/5">
    <i class="bx bx-cloud-upload text-3xl text-brand-500"></i>
    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
        @if($field->isDisabledForAction($action ?? null))
            Image browsing is disabled
        @else
            Click to browse your images
        @endif
    </span>
    <span class="text-xs text-gray-400">1600 x 1200 (4:3) recommended. PNG, JPG and GIF files are allowed</span>
</button>

<div id="dropzone-preview{{$inputId}}" class="mb-4 {{ empty($value) ? 'hidden' : '' }}">
    <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-white/5">
            <a href="/{{is_string($value) ? $value : \Nodex\Nexus\helpers\Image::getStorageImage($value)}}"
               target="_blank" rel="noopener" id="{{$inputId}}_link">
                <img src="/{{is_string($value) ? $value : \Nodex\Nexus\helpers\Image::getStorageImage($value)}}"
                    id="{{$inputId}}_prev"
                    class="max-h-20 max-w-20 object-contain">
            </a>
        </div>
        <div class="flex-1"></div>
        @if(!($field->isDisabledForAction($action ?? null)))
            <button type="button" id="delete_image_{{$inputId}}"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10">
                <i class="{{ nexus_icon('delete') }}"></i>
            </button>
        @endif
    </div>
</div>

@push('js')
    <script>
        (function () {
            const input = document.getElementById('{{$inputId}}');
            const preview = document.getElementById('{{$inputId}}_prev');
            const link = document.getElementById('{{$inputId}}_link');
            const wrapper = document.getElementById('dropzone-preview{{$inputId}}');
            const deleteBtn = document.getElementById('delete_image_{{$inputId}}');

            if (!input) return;

            deleteBtn?.addEventListener('click', function () {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                wrapper.classList.add('hidden');
            });

            input.addEventListener('input', function () {
                const val = input.value.replaceAll('\\', '/');
                if (preview) preview.setAttribute('src', '/' + val);
                if (link) link.setAttribute('href', '/' + val);
                wrapper.classList.toggle('hidden', val.trim() === '');
            });
        })();
    </script>
@endpush
