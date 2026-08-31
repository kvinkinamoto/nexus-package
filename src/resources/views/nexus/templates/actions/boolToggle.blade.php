<div class="form-check form-switch">
    {{--        @dd($fieldName)--}}
    <input type="hidden" name="fieldName" value="{{$fieldName}}">
    <input name="{{$fieldName}}-toggle"
           class="form-check-input"
           type="checkbox"
           role="switch"
           id="{{$fieldName}}_toggle_{{ $item->id }}"
           onchange="this.form.requestSubmit()"
           @if($item->{$fieldName} == 1) checked @endif>
</div>
