@if(session('nexus_impersonator_id'))
    <div class="sticky top-0 z-999999 flex items-center justify-center gap-3 bg-warning-500 px-3 py-2 text-sm text-white">
        <span class="flex items-center gap-1.5">
            <i class="{{ nexus_icon('impersonate') }}"></i>
            {{ __('nexus::translate.now_impersonating', ['name' => auth()->user()->name ?? auth()->user()->email ?? auth()->id()]) }}
        </span>
        <form method="POST" action="{{ route('nexus.impersonate.stop') }}" class="m-0">
            @csrf
            <button type="submit" class="rounded-md bg-gray-900/80 px-3 py-1 text-xs font-medium text-white hover:bg-gray-900">
                {{ __('nexus::translate.stop_impersonating') }}
            </button>
        </form>
    </div>
@endif
