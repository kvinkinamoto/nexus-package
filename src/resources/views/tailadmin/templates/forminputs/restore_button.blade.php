<form action="{{ route($route, $param) }}" method="POST"
      id="restoreButton_{{str_replace('.','_',$route)}}_{{$param['id']}}">
    @csrf

    <button type="submit" title="@lang('nexus::translate.Restore')"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10">
        <i class="{{ nexus_icon('restore') }} text-base"></i>
    </button>
</form>
