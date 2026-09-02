@php
    $value = '';
    if(isset($tab_lang)){
        if(isset($model)){
            $value = old($field->name.'.'.$tab_lang, ($model->getTranslation($field->name, $tab_lang,false)));
        } else {
            $value = old($field->name.'.'.$tab_lang, $defaultValue);
        }
    } else {
        if(isset($model)){
            $value = old($field->name,($model->{$field->name}));
        } else {
            $value = old($field->name, $defaultValue);
        }
    }
    if(!isset($value)){
        $value = $defaultValue;
    }

    $selectClass = 'h-11 w-full appearance-none rounded-lg border bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-900 dark:text-white/90 ' . ($errors->has($field->name) ? 'border-error-500' : 'border-gray-300 dark:border-gray-700');
@endphp
<div class="mb-4">
    <label for="{{$field->name}}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
        @if(isset($field->label) && !empty($field->label))
            @lang($moduleName.'::translate.'.$field->label)
        @else
            @lang($moduleName.'::translate.'.$field->name) {{$tab_lang??''}}
        @endif
        @if(in_array($field->name,$modelSchema['required']??[]))
            *
        @endif
    </label>

    <select class="{{ $selectClass }}"
            data-choices
            data-choices-sorting-false

            id="parent-select"
            data-url="{{ $route }}"

            @if(isset($tab_lang))
                name="{{$field->name}}[{{$tab_lang}}]"
            id="{{$field->name}}[{{$tab_lang}}]"
            @else
                name="{{$field->name}}"
            id="{{$field->name}}"
            @endif

            @if(in_array($field->name,$modelSchema['required']??[]))
                required
            @endif
    >
        <option value=""> @lang('nexus::translate.choose')</option>

        @foreach($customData as $data)
            <option
                    value="{{ $data->type() }}"
                    @if($data->type() == $value) selected @endif
            >
                {{$data->label}}
            </option>
        @endforeach
    </select>

    {!! $errors->first($field->name, '<p class="mt-1.5 text-xs text-error-500">:message</p>') !!}
</div>

<div class="mb-4" id="child-wrapper">
    <div id="child-select-wrapper" @if(empty($value)) class="hidden" @endif>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
            @lang($moduleName.'::translate.child_field')
        </label>

        <select
                class="{{ $selectClass }}"
                id="child-select"
                name="{{$customData[0]?->morphId}}"
        >
            <option value="">@lang('nexus::translate.choose')</option>
            @if(!empty($defaultValue))
                @foreach($defaultValue as $key=>$value)
                    <option
                            value="{{ $key }}"
                            @if($model->{$customData[0]?->morphId} == $key) selected @endif
                    >
                        {{$value}}
                    </option>
                @endforeach
            @endif
        </select>
    </div>

    <div id="child-input-wrapper" @if(!empty($value) && $value !== 'URL') class="hidden" @endif>
        <label for="{{ $fieldPrefix }}_url" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">{{ $urlLabel }}</label>

        <input type="text"
               class="{{ $selectClass }}"
               name="url"
               id="{{ $fieldPrefix }}_url"
               value="{{ old('url', $model->url ?? '') }}"
               placeholder="https://...">
    </div>

</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const parentSelect = document.getElementById('parent-select');
            const childSelect = document.getElementById('child-select');
            const childInput = document.getElementById('{{ $fieldPrefix }}_url');
            const childSelectWrapper = document.getElementById('child-select-wrapper');
            const childInputWrapper = document.getElementById('child-input-wrapper');
            if (!parentSelect) return;

            function showInput() {
                childSelect.value = '';
                childInput.value = '';
                childSelectWrapper.classList.add('hidden');
                childInputWrapper.classList.remove('hidden');
                childSelect.disabled = true;
                childInput.disabled = false;
            }

            function showSelect() {
                childInputWrapper.classList.add('hidden');
                childSelectWrapper.classList.remove('hidden');
                childInput.disabled = true;
                childSelect.disabled = false;
            }

            parentSelect.addEventListener('change', function () {
                const parentValue = parentSelect.value;
                const url = parentSelect.dataset.url;

                if (parentValue === '' || parentValue === 'URL') {
                    showInput();
                    return;
                }

                showSelect();

                childSelect.innerHTML = '<option>@lang('nexus::translate.load')...</option>';

                const params = new URLSearchParams({ fieldName: 'name', parentValue });

                fetch(url + '?' + params.toString())
                    .then((r) => r.json())
                    .then((response) => {
                        let options = '<option value="">@lang('nexus::translate.choose')</option>';
                        Object.entries(response).forEach(([value, label]) => {
                            options += `<option value="${value}">${label}</option>`;
                        });
                        childSelect.innerHTML = options;
                    })
                    .catch(() => {
                        childSelect.innerHTML = '<option>@lang('nexus::translate.error')</option>';
                    });
            });
        });
    </script>
@endpush
