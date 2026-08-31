<?php
if (is_object($item)) {
    $param = $item->id;
} elseif (is_string($item)) {
    $param = $item;
}

?>
<form method="POST" action="{{ route($route, [$param]) }}" class="mr-1"
      onsubmit="return publishButtonForm(deleteButton_{{str_replace('.','_',$route)}}_{{$param}})" method="POST"
      id="deleteButton_{{str_replace('.','_',$route)}}_{{$param}}">
    @csrf
    <button type="submit" class="btn btn-xs btn-block @if($item->isPublish()) btn-success @else btn-danger @endif"  style="width: 30px; height: 30px">
        <span class="fa fa-eye"></span>
    </button>
</form>
@section('js')
    @parent
    <script>
        function publishButtonForm(id) {
            return true;
        }
    </script>
@stop
