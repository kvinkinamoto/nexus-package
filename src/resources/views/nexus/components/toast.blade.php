@php
    $type = $type ?? 'success';

    $bgClass = $type === 'warning' ? 'bg-warning' : 'bg-success';
    $icon = $type === 'warning' ? nexus_icon('error') : nexus_icon('success');
@endphp

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="{{ config('nexus.toast.delay') }}">
        <div class="toast-header">
            <div class="auth-logo me-auto d-flex align-items-center {{ $bgClass }} rounded p-1">
                <i class="{{ $icon }} text-white fs-4"></i>
                <span class="ms-2 text-white text-uppercase" style="font-weight: 500;">
                    {{ $type }}
                </span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            {{ $message }}
        </div>
    </div>
</div>
