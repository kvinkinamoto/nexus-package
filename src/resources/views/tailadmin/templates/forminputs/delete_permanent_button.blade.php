<form action="{{ route($route, $param) }}" method="POST"
    id="deleteButton_{{str_replace('.', '_', $route)}}_{{$param['id']}}" class="flex items-center">
    @method('DELETE')
    @csrf
    <button type="submit" title="@lang('nexus::translate.Delete')"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-error-500 hover:bg-error-50 dark:hover:bg-error-500/10">
        <i class="{{ nexus_icon('delete') }} text-base"></i>
    </button>
</form>
