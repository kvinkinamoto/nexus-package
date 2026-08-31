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

//    @dd($value)
@endphp
{{--@dd($customData)--}}
<div class="mb-3">
    <label for="{{$field->name}}" class="form-label">
        @if(isset($field->label) && !empty($field->label))
            @lang($moduleName.'::translate.'.$field->label)
        @else
            @lang($moduleName.'::translate.'.$field->name) {{$tab_lang??''}}
        @endif
        @if(in_array($field->name,$modelSchema['required']??[]))
            *
        @endif
    </label>

    <select class="form-control @error($field->name) is-invalid @enderror"
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

    {!! $errors->first($field->name, '<small class="error invalid-feedback">:message</small>') !!}
</div>

<div class="mb-3" id="child-wrapper">
{{--    @dd($customData)--}}
    {{-- SELECT (додано перевірку на empty($value)) --}}
    <div id="child-select-wrapper" @if(empty($value)) style="display:none;" @endif>
        <label class="form-label">
            @lang($moduleName.'::translate.child_field')
        </label>

        <select
                class="form-control"
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

    {{-- URL INPUT (показується, якщо значення порожнє) --}}
    <div id="child-input-wrapper" @if(!empty($value) && $value !== 'URL') style="display:none;" @endif>
        <label for="{{ $fieldPrefix }}_url">{{ $urlLabel }}</label>

        <input type="text"
               class="form-control"
               name="url"
               id="{{ $fieldPrefix }}_url"
               value="{{ old('url', $model->url ?? '') }}"
               placeholder="https://...">
    </div>

</div>

{{--@dd($field, $model)--}}
@push('js')
    <script>
        $(document).ready(function () {

            function showInput() {
                $('#child-select').val('');
                $('#child-input').val('');
                $('#child-select-wrapper').hide();
                $('#child-input-wrapper').show();
                $('#child-select').prop('disabled', true);
                $('#child-input').prop('disabled', false);
                console.log('show input')
            }

            function showSelect() {
                $('#child-input-wrapper').hide();
                $('#child-select-wrapper').show();
                $('#child-input').prop('disabled', true);
                $('#child-select').prop('disabled', false);
            }

            $('#parent-select')
                .off('change')
                .on('change', function () {

                    let parentValue = $(this).val();
                    let url = $(this).data('url');

                    // 👉 Якщо значення порожнє (choose type) або дорівнює URL
                    if (parentValue === '' || parentValue === 'URL') {
                        showInput();
                        return;
                    }

                    // 👉 В інших випадках — select
                    showSelect();

                    $('#child-select').html('<option>@lang('nexus::translate.load')...</option>');

                    const fieldName = @json($field->name);

                    $.ajax({
                        url: url,
                        type: 'get',
                        data: {
                            'fieldName': 'name',
                            'parentValue': parentValue
                        },
                        success: function (response) {
                            console.log(response)
                            let options = '<option value="">@lang('nexus::translate.choose')</option>';

                            Object.entries(response).forEach(([value, label]) => {
                                options += `<option value="${value}">${label}</option>`;
                            });

                            $('#child-select').html(options);
                        },
                        error: function () {
                            $('#child-select').html('<option>@lang('nexus::translate.error')</option>');
                        }
                    });
                });

        });
    </script>
@endpush
