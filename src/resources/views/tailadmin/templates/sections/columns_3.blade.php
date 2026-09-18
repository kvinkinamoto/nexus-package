@section('sectionFieldsNoTranslates' . $section->name)
    @php $fieldsRender = []; @endphp
    @foreach($modelSchema['fields'] as $fieldName => $field)
        @if(!$field->isTranslate && $field->section == $section->name)
            @php
                $fieldsRender[$fieldName] = $field;
            @endphp
        @endif
    @endforeach

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($fieldsRender as $fieldName => $field)
            @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                'field' => $field,
                'module' => $module,
                'model' => $model ?? null,
                'action' => $action ?? null,
                'formData' => $formData ?? [],
                'modelSchema' => $modelSchema ?? [],
            ])
        @endforeach
    </div>
@stop

@section('sectionFieldsTranslates' . $section->name)
    @php $fieldsRender = []; @endphp
    @foreach($modelSchema['fields'] as $fieldName => $field)
        @if($field->section == $section->name && $field->isTranslate)
            @php
                $fieldsRender[$fieldName] = $field;
            @endphp
        @endif
    @endforeach

    @foreach($modelSchema['languages'] as $lang)
        <div x-show="activeLangTab{{ $section->name }} === '{{ $lang }}'" x-cloak
             id="tab-{{$lang}}-{{$section->name}}">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($fieldsRender as $fieldName => $field)
                    @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                        'field' => $field,
                        'module' => $module,
                        'model' => $model ?? null,
                        'action' => $action ?? null,
                        'tab_lang' => $lang,
                        'formData' => $formData ?? [],
                        'modelSchema' => $modelSchema ?? [],
                    ])
                @endforeach
            </div>
        </div>
    @endforeach
@stop

@include('nexus::'. config('nexus.template').'.templates.sections.layout')
