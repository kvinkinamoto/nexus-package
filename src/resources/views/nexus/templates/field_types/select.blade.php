@php
    $value = '';
    if (isset($tab_lang)) {
        if (isset($model)) {
            $value = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
        } else {
            $value = old($field->name . '.' . $tab_lang, $field->defaultValue);
        }
    } else {
        if (isset($model)) {
            $value = old($field->name, ($model->{$field->name}));
        } else {
            $value = old($field->name, $field->defaultValue);
        }
    }
    if (!isset($value)) {
        $value = $field->defaultValue;
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
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{ $value }}">
    @endif

    <select class="form-control @error($field->name) is-invalid @enderror" data-choices data-choices-sorting-false
        @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" id="{{$field->name}}[{{$tab_lang}}]" @else
        name="{{$field->name}}" id="{{$field->name}}" @endif @if($field->isRequired ?? false) required @endif
        @if($field->isDisabledForAction($action ?? null)) disabled @endif>
        <option value="">
            @lang('nexus::translate.chooseOption')
        </option>
        @foreach($field->customData as $data)
            <option value="{{ $data['value'] }}" @if($data['value'] == $value) selected @endif>
                {{ $data['name'] }}
            </option>
        @endforeach
    </select>

    {!! $errors->first($field->name, '<small class="error invalid-feedback">:message</small>') !!}
</div>
