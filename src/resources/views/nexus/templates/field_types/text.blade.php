@php
    $errorsField = $field->name;
    if (isset($tab_lang)) {
        $errorsField = $field->name . '.' . $tab_lang;
    }
@endphp

<div class="mb-3">
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
        class="form-control @error($errorsField) is-invalid @enderror"
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        id="{{$field->name}}_{{$tab_lang ?? ''}}">{{$showValue}}</textarea>
    <div @if(isset($tab_lang)) id="validation{{$field->name}}[{{$tab_lang}}]" @else id="validation{{$field->name}}"
    @endif class="invalid-feedback">
        {{ $errors->first($errorsField) }}
    </div>
</div>
@section('js')
    @parent
    <script>
        jQuery(document).ready(function () {
            let options = {
                filebrowserImageBrowseUrl: '/elfinder/ckeditor',
                allowedContent: true
            };
            @if($field->isEditor ?? false)
                CKEDITOR.replace('{{$field->name}}_{{$tab_lang ?? ""}}'
                    , options
                );
            @endif
            });
    </script>
@endsection