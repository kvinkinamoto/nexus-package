@php
    $value = '';
    if (isset($tab_lang)) {
        if (isset($model)) {
            $value = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
        } else {
            $value = old($field->name . '.' . $tab_lang, $field->default);
        }
    } else {
        if (isset($model)) {
            $value = old($field->name, $model->{$field->name});
        } else {
            $value = old($field->name, $field->default);
        }
    }
    if (!isset($value)) {
        $value = $field->default;
    }

    $errorsField = $field->name;
    if (isset($tab_lang)) {
        $errorsField = $field->name . '.' . $tab_lang;
    }

    $inputClass = 'h-11 w-full rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($errorsField) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
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
    @if($field->isDisabledForAction($action ?? null))
        <input type="hidden" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else name="{{$field->name}}" @endif value="{{ $value }}">
    @endif
    <input type="number" min="0" step="any" @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" @else
    name="{{$field->name}}" @endif value="{{ $value }}" @if($field->isRequired ?? false) required @endif
        @if($field->isDisabledForAction($action ?? null)) disabled @endif
        class="{{ $inputClass }}" id="{{$field->name}}">
    <div @if(isset($tab_lang)) id="validation{{$field->name}}[{{$tab_lang}}]" @else id="validation{{$field->name}}"
    @endif class="mt-1.5 text-xs text-error-500">
        {{ $errors->first($errorsField) }}
    </div>
</div>
