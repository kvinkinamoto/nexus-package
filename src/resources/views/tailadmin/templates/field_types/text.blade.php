@php
    $errorsField = $field->name;
    if (isset($tab_lang)) {
        $errorsField = $field->name . '.' . $tab_lang;
    }
    $areaClass = 'w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorsField) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700');
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
    <?php
if (isset($tab_lang)) {
    if (isset($model)) {
        $showValue = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
    } else {
        $showValue = old($field->name . '.' . $tab_lang);
    }
} else {
    if (isset($model)) {
        $showValue = old($field->name, ($model->{$field->name}));
    } else {
        $showValue = old($field->name);
    }
}
    ?>
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{ $showValue }}">
    @endif
    <textarea @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif rows="4"
        class="{{ $areaClass }}"
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        id="{{$field->name}}_{{$tab_lang ?? ''}}">{{$showValue}}</textarea>
    <div @if(isset($tab_lang)) id="validation{{$field->name}}[{{$tab_lang}}]" @else id="validation{{$field->name}}"
    @endif class="mt-1.5 text-xs text-error-500">
        {{ $errors->first($errorsField) }}
    </div>
</div>
@section('js')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const options = {
                filebrowserImageBrowseUrl: '/elfinder/ckeditor',
                allowedContent: true
            };
            @if($field->isEditor ?? false)
                CKEDITOR.replace('{{$field->name}}_{{$tab_lang ?? ""}}', options);
            @endif
        });
    </script>
@endsection
