{{--<form method="POST"--}}
{{--      action="{{route('admin.get_action', ['action'=>'ordering','moduleName'=>$moduleName,'id'=>$item->id])}}"--}}
{{--      class="d-flex gap-1">--}}
{{--    @csrf--}}
    <input type="hidden" name="level" value="1">
    <button class="btn btn-light btn-sm" name="ordering" value="up" type="submit">
        <i class="{{ nexus_icon('arrow_up') }}"></i>
    </button>
    <button class="btn btn-light btn-sm" name="ordering" value="down" type="submit">
        <i class="{{ nexus_icon('arrow_down') }}"></i>
    </button>
{{--</form>--}}
