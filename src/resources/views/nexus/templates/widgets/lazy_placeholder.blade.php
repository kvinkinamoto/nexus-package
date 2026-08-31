{{--
    Rendered instead of a lazy widget's real HTML (#[Widget(lazy: true)]) —
    the inline script in pages/dashboard.blade.php fetches the real card from
    NexusController::widgetCard() and swaps it in after DOMContentLoaded.
--}}
<div class="card nexus-widget-lazy" data-nexus-widget-lazy="{{ $meta->name }}">
    <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 120px;">
        <div class="spinner-border text-muted" role="status">
            <span class="visually-hidden">{{ $meta->label }}</span>
        </div>
    </div>
</div>
