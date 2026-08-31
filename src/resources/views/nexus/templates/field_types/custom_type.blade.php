@inject('customFieldType', $field->customFiledType )
@if($customFieldType->renderField($model??null, $field, $modelSchema, $lang??null) instanceof \Illuminate\View\View)
    @include($customFieldType->renderField($model??null, $field, $modelSchema, $lang??null)->getName() )
@else
    {!! $customFieldType->renderField($model??null, $field, $modelSchema, $lang??null) !!}
@endif



