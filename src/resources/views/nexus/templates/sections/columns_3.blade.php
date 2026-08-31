@section('sectionFieldsNoTranslates' . $section->name)
    @php $fieldsRender = []; @endphp
    @foreach($modelSchema['fields'] as $fieldName => $field)
        @if(!$field->isTranslate && $field->section == $section->name)
            @php
                $fieldsRender[$fieldName] = $field;
            @endphp
        @endif
    @endforeach

    <div class="row">
        @php $index = 0 @endphp
        @foreach($fieldsRender as $fieldName => $field)
            @php $index++; @endphp
            <div class="col-lg-4">
                @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                    'field' => $field,
                    'module' => $module,
                    'model' => $model ?? null,
                    'action' => $action ?? null,
                    'formData' => $formData ?? [],
                    'modelSchema' => $modelSchema ?? [],
                ])
            </div>
            @if($index % 3 == 0 && $index != count($fieldsRender)) </div>
    <div class="row"> @endif
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
        <div class="tab-pane fade @if($loop->first) active show @endif"
             id="tab-{{$lang}}-{{$section->name}}">
            <div class="row">
                @php $index = 0 @endphp
                @foreach($fieldsRender as $fieldName => $field)
                    @php $index++; @endphp
                    <div class="col-lg-4">
                        @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                            'field' => $field,
                            'module' => $module,
                            'model' => $model ?? null,
                            'action' => $action ?? null,
                            'tab_lang' => $lang,
                            'formData' => $formData ?? [],
                            'modelSchema' => $modelSchema ?? [],
                        ])
                    </div>

                    @if($index % 3 == 0 && $index != count($fieldsRender)) </div>
            <div class="row"> @endif

                @endforeach
            </div>
        </div>
    @endforeach
@stop

@include('nexus::'. config('nexus.template').'.templates.sections.layout')
