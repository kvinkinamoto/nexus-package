<div class="mb-3">
    <?php
/**
 * @var \Illuminate\Foundation\Http\FormRequest
 *
 */
        ?>
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
    <input type="password" name="{{$field->name}}" value="{{old($field->name)}}" @if(($field->isRequired ?? false) && $action != 'update') required @endif
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        class="form-control @error($field->name) is-invalid @enderror"
        id="{{$field->name}}" autocomplete="new-password">
    {!! $errors->first($field->name, '<small class="error invalid-feedback">:message</small>') !!}
</div>