@php
    $boolValue = '0';
    if(isset($model)) {
        $boolValue = old($field->name, $model->{$field->name});
    } else {
        $boolValue = old($field->name, '0');
    }

    $boolValue = $boolValue ? '1' : '0';
@endphp
<div class="mb-4">
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" name="{{$field->name}}" value="{{ $boolValue }}">
    @else
        <input type="hidden" name="{{$field->name}}" value="0">
    @endif
    <label class="flex cursor-pointer items-center gap-2.5">
        <span class="relative inline-flex items-center">
            <input
                class="peer sr-only"
                type="checkbox"
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
            <span class="h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700"></span>
            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition peer-checked:translate-x-5"></span>
        </span>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-400">
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
        </span>
    </label>
    {!! $errors->first($field->name, '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>
