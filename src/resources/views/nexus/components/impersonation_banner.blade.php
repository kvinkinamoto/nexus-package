@if(session('nexus_impersonator_id'))
    <div class="d-flex align-items-center justify-content-center gap-3 py-2 px-3"
         style="background: var(--bs-warning, #f1b44c); color: #000; position: sticky; top: 0; z-index: 1060;">
        <span>
            <i class="{{ nexus_icon('impersonate') }} align-middle me-1"></i>
            {{ __('nexus::translate.now_impersonating', ['name' => auth()->user()->name ?? auth()->user()->email ?? auth()->id()]) }}
        </span>
        <form method="POST" action="{{ route('nexus.impersonate.stop') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-dark btn-sm">
                {{ __('nexus::translate.stop_impersonating') }}
            </button>
        </form>
    </div>
@endif
