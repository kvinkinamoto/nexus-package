<form action="{{ route($route, $param) }}" method="POST"
    id="deleteButton_{{str_replace('.', '_', $route)}}_{{$param['id']}}" class="d-flex align-items-center">
    @method('DELETE')
    @csrf
    <button type="submit" class="btn btn-soft-danger btn-sm" title="@lang('nexus::translate.Delete')">
        <i class="{{ nexus_icon('delete') }} align-middle fs-18"></i>
    </button>
</form>