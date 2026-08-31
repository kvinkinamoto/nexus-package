@inject('customFieldType', $section->customFieldType )
@if($customFieldType->render($model??null, $section, $modelSchema) instanceof \Illuminate\View\View)
    @include($customFieldType->render($model??null, $section, $modelSchema)->getName() )
@else
    {!! $customFieldType->render($model??null, $section, $modelSchema) !!}
@endif



