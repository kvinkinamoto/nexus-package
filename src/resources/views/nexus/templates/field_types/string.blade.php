@php
    //dd($field);
    $value = '';
    if (isset($tab_lang)) {
        if (isset($model)) {
            $value = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
        } else {
            $value = old($field->name . '.' . $tab_lang, $field->defaultValue ?? '');
        }
    } else {
        if (isset($model)) {
            $value = old($field->name, $model->{$field->name});
        } else {
            $value = old($field->name, $field->defaultValue ?? '');
        }
    }
    if (!isset($value)) {
        $value = $field->defaultValue ?? '';
    }

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

    <div class="input-group has-validation">
        @if($field->isDisabledForAction($action ?? null))
            <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{ $value }}">
        @endif

        <input type="text" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]"
        id="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" id="{{$field->name}}" @endif
            value="{{ $value }}" @if($field->isRequired ?? false) required @endif
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            class="form-control @error($errorsField) is-invalid @enderror">
        <div @if(isset($tab_lang)) id="validation{{$field->name}}[{{$tab_lang}}]" @else id="validation{{$field->name}}"
        @endif class="invalid-feedback">
            {{ $errors->first($errorsField) }}
        </div>
    </div>

</div>
