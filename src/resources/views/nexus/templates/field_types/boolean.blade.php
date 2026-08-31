<div class="form-check form-switch mb-2">
    @php
        $boolValue = '0';
        if(isset($model)) {
            $boolValue = old($field->name, $model->{$field->name});
        } else {
            $boolValue = old($field->name, '0');
        }

        $boolValue = $boolValue ? '1' : '0';
    @endphp
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" name="{{$field->name}}" value="{{ $boolValue }}">
    @else
        <input type="hidden" name="{{$field->name}}" value="0">
    @endif
    <input
        class="form-check-input @error('is_published') is-invalid @enderror"
        type="checkbox"
        role="switch"
        @if(isset($model))
            @if (old($field->name,$model->{$field->name})=='1') checked @endif
        @else
            @if (old($field->name)=='1') checked @endif
        @endif
        @if($field->isRequired??false)
            required
        @endif
        @if($field->isDisabledForAction($action ?? null))
            disabled
        @endif
        value="1"
        id="{{$field->name}}"
        name="{{$field->name}}"
    >
    <label for="{{$field->name}}" class="form-label">
        @if(isset($field->label) && !empty($field->label))
            @if(str_contains($field->label, '::'))
                @lang($field->label)
            @else
                @lang(lcfirst($module->name).'::translate.'.$field->label)
            @endif
        @else
            @if(str_contains($field->name, '::'))
                @lang($field->name)
            @else
                @lang(lcfirst($module->name).'::translate.'.$field->name)
            @endif {{$tab_lang??''}}
        @endif
    </label>
    {!! $errors->first($field->name, '<small class="error invalid-feedback">:message</small>') !!}
</div>
