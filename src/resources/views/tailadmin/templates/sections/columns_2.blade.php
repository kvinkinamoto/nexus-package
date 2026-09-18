@section('sectionFieldsNoTranslates' . $section->name)
    @php $fieldsRender = []; @endphp
    @foreach($formData['fields'] as $field)
        <?php
        $condition = (!$field->isTranslate && $field->section == $section->name);
        ?>
        @if($condition)
            @php
                $fieldsRender[$field->name] = $field;
            @endphp
        @endif
    @endforeach

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach($fieldsRender as $field)
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
    @foreach($formData['fields'] as $field)
        @if($field->section == $section->name && ($field->isTranslate))
            @php
                $fieldsRender[$field->name] = $field;
            @endphp
        @endif
    @endforeach
    @foreach($formData['languages']??[] as $lang)
        <div x-show="activeLangTab{{ $section->name }} === '{{ $lang }}'" x-cloak
             id="tab-{{$lang}}-{{$section->name}}">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($fieldsRender as $field->name => $field)
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
