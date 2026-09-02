@php
    $value = '';
    if (isset($tab_lang)) {
        if (isset($model)) {
            $value = old($field->name . '.' . $tab_lang, ($model->getTranslation($field->name, $tab_lang, false)));
        } else {
            $value = old($field->name . '.' . $tab_lang, $field->defaultValue);
        }
    } else {
        if (isset($model)) {
            $value = old($field->name, ($model->{$field->name}));
        } else {
            $value = old($field->name, $field->defaultValue);
        }
    }
    if (!isset($value)) {
        $value = $field->defaultValue;
    }

    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($field->name) ? 'border-error-500 focus:ring-3 focus:ring-error-500/10' : 'border-gray-300 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700');
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

    <select class="{{ $selectClass }}" data-choices data-choices-sorting-false
        @if(isset($tab_lang)) name="{{$field->name}}[{{$tab_lang}}]" id="{{$field->name}}[{{$tab_lang}}]" @else
        name="{{$field->name}}" id="{{$field->name}}" @endif @if($field->isRequired ?? false) required @endif
        @if($field->isDisabledForAction($action ?? null)) disabled @endif>
        <option value="">
            @lang('nexus::translate.chooseOption')
        </option>
        @foreach($field->customData as $data)
            <option value="{{ $data['value'] }}" @if($data['value'] == $value) selected @endif>
                {{ $data['name'] }}
            </option>
        @endforeach
    </select>

    {!! $errors->first($field->name, '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>
