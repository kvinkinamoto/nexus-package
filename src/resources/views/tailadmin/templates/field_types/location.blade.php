@php
    $inputClass = 'h-11 w-full rounded-lg border bg-transparent pl-10 pr-4 py-2.5 text-sm text-gray-800 shadow-theme-xs disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($field->name) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700');
@endphp
<div class="mb-4">
    <label for="{{$field->name}}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
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
    <div class="relative">
        <i class="{{ nexus_icon('location') }} pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        @if($field->isDisabledForAction($action ?? null))
            @php
                $locationValue = isset($tab_lang)
                    ? (isset($model) ? old($field->name . '.' . $tab_lang, $model->getTranslation($field->name, $tab_lang, false)) : old($field->name . '.' . $tab_lang))
                    : (isset($model) ? old($field->name, $model->{$field->name}) : old($field->name));
            @endphp
            <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{ $locationValue }}">
        @endif
        <input type="text" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}"
        @endif @if(isset($tab_lang)) @if(isset($model))
                value="{{old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)))}}" @else
            value="{{old($field->name . '.' . $tab_lang)}}" @endif @else @if(isset($model))
            value="{{old($field->name, ($model->{$field->name}))}}" @else value="{{old($field->name)}}" @endif @endif
            @if($field->isRequired ?? false) required @endif
            @if($field->isDisabledForAction($action ?? null)) disabled @endif
            class="{{ $inputClass }}" id="{{$field->name}}">
    </div>
    {!! $errors->first($field->name, '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>
