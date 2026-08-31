@section('sectionFieldsNoTranslates' . $section->name)
    @foreach($formData['fields'] as $field)
        {{--        @dd($field,$section)--}}
        @if(!($field->isTranslate??false))
            @if($field->section == $section->name)
                @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                    'field' => $field,
                    'module' => $module,
                    'model' => $model ?? null,
                    'action' => $action ?? null,
                    'formData' => $formData ?? [],
                    'modelSchema' => $modelSchema ?? [],
                ])
            @endif
        @endif
    @endforeach
@stop

@section('sectionFieldsTranslates' . $section->name)
    @foreach($formData['languages']??[] as $lang)
        <div class="tab-pane fade @if($loop->first) active show @endif"
             id="tab-{{$lang}}-{{$section->name}}">
            <div class="row">
                @foreach($formData['fields'] as $field)
                    @if($field->section == $section->name && $field->isTranslate)
                        @include('nexus::'. config('nexus.template').'.templates.sections._field', [
                            'field' => $field,
                            'module' => $module,
                            'model' => $model ?? null,
                            'action' => $action ?? null,
                            'tab_lang' => $lang,
                            'formData' => $formData ?? [],
                            'modelSchema' => $modelSchema ?? [],
                        ])
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
@stop

@include('nexus::'. config('nexus.template').'.templates.sections.layout')
