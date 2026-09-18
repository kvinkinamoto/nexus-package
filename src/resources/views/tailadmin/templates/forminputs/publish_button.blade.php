<?php
if (is_object($item)) {
    $param = $item->id;
} elseif (is_string($item)) {
    $param = $item;
}
?>
<form method="POST" action="{{ route($route, [$param]) }}"
      onsubmit="return publishButtonForm(deleteButton_{{str_replace('.','_',$route)}}_{{$param}})"
      id="deleteButton_{{str_replace('.','_',$route)}}_{{$param}}">
    @csrf
    <button type="submit"
        class="flex h-8 w-8 items-center justify-center rounded-lg {{ $item->isPublish() ? 'text-success-600 hover:bg-success-50 dark:hover:bg-success-500/10' : 'text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10' }}">
        <i class="{{ nexus_icon('eye') }} text-base"></i>
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
