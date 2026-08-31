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
    <div class="input-group date has-validation">
        @if($field->isDisabledForAction($action ?? null))
            <input type="hidden" name="{{$field->name}}" value="{{ old($field->name, isset($model) ? $model->{$field->name} : '') }}">
        @endif
        <input type="text" class="form-control datetimepicker-input" id="{{$field->name}}"
            data-target="#{{$field->name}}" name="{{$field->name}}" @error($field->name) is-invalid @enderror
            @if($field->isRequired ?? false) required @endif
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            value="{{ old($field->name, isset($model) ? $model->{$field->name} : '') }}">
        <div @if(isset($tab_lang)) id="validation{{$field->name}}[{{$tab_lang}}]" @else id="validation{{$field->name}}"
        @endif class="invalid-feedback">
            {{ $errors->first($field->name) }}
        </div>
    </div>
</div>

@section('js')
    @parent

    <script>
        // 18:41
        jQuery(document).ready(function () {
            jQuery('#{{$field->name}}').flatpickr({
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                time_24hr: true
            });
        })
    </script>
@endsection