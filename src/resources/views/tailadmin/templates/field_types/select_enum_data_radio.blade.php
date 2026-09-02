<div class="mb-4">
    <label for="{{$field->name}}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
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
    <div class="flex flex-wrap gap-3">
        @foreach ($field->default as $index => $value)
            <label class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-gray-300 text-xs has-checked:border-brand-500 has-checked:bg-brand-500 has-checked:text-white dark:border-gray-700"
                title="{{$value}}">
                <input class="sr-only" type="radio" id="customRadio{{ $index }}" name="align" value="{{$value}}"
                       @if ($value == old($field->name, $model->{$field->name}??''))
                           checked
                        @endif
                        @if($field->isDisabledForAction($action ?? null)) disabled @endif>
            </label>
        @endforeach
    </div>
</div>
