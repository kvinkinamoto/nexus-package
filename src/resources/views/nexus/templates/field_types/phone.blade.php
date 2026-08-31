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
        <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{ old($field->name, isset($model) ? $model->{$field->name} : '') }}">
    @endif
    <div class="input-group mb-3">
        <span class="input-group-text fs-20">
            <i class="{{ nexus_icon('phone') }} fs-20"></i>
        </span>
        <input type="text" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}"
        @endif @if(isset($tab_lang)) @if(isset($model))
                value="{{old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)))}}" @else
            value="{{old($field->name . '.' . $tab_lang)}}" @endif @else @if(isset($model))
            value="{{old($field->name, ($model->{$field->name}))}}" @else value="{{old($field->name)}}" @endif @endif
            @if($field->isRequired ?? false) required @endif
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            class="form-control @error($field->name) is-invalid @enderror" id="{{$field->name}}">
    </div>
    {!! $errors->first($field->name, '<small class="error invalid-feedback">:message</small>') !!}
</div>

@section('js')
<script src="https://unpkg.com/imask"></script>
<script>
    const phoneInput = document.getElementById('{{$field->name}}');

    const mask = IMask(phoneInput, {
        mask: '+{380} (00)-00-00-000',
        lazy: false,
    });

    const form = phoneInput.closest('form');
    form.addEventListener('submit', function () {
        phoneInput.value = '+' + phoneInput.value.replace(/\D/g, '');
    });
</script>
@stop