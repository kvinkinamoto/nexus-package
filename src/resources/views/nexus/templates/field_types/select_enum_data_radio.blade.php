<div class="form-group">
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
        @if($field->isRequired??false)
            *
        @endif
    </label>
    <div class="row">
        @foreach ($field->default as $index => $value)
            @if ($index % 3 === 0 && $index !== 0)
    </div>
    <div class="row">
        @endif
        <div class="col-md-1">
            <div class="custom-control custom-radio">
                <input class="custom-control-input" type="radio" id="customRadio{{ $index }}" name="align" value="{{$value}}"
                       @if ($value == old($field->name, $model->{$field->name}??''))
                           checked
                        @endif
                        @if($field->isDisabledForAction($action ?? null)) disabled @endif>
                <label for="customRadio{{ $index }}" class="custom-control-label" title="{{$value}}"></label>
            </div>
        </div>
        @endforeach
    </div>
</div>
