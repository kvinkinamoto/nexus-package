<form action="{{ route($route, $param) }}" class="mr-2" method="POST"
      id="restoreButton_{{str_replace('.','_',$route)}}_{{$param['id']}}">
    @csrf

    <button type="submit" class="btn btn-soft-danger btn-sm" title="@lang('nexus::translate.Restore')">
        <i class="{{ nexus_icon('restore') }} align-middle fs-18"></i>
    </button>
</form>
