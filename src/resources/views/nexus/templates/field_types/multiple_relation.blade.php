@php
    $value = '';
    if (isset($model)) {
        $value = old($field->name, implode(',', array_column(($model->{$field->name}->toArray()), $field->customData)));
    } else {
        $value = old($field->name, $field->defaultValue);
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
        <input type="hidden" name="{{$field->name}}[]" value="{{ $value }}">
    @endif
    <input type="text" data-choices data-choices-text-unique-true data-choices-removeItem name="{{$field->name}}[]"
        id="{{$field->name}}" value="{{ $value }}" @if($field->isRequired ?? false) required @endif
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        class="form-control @error($field->name) is-invalid @enderror">
    {!! $errors->first($field->name, '<small class="error invalid-feedback">:message</small>') !!}
</div>