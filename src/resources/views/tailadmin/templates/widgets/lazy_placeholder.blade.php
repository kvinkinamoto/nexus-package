{{--
    Rendered instead of a lazy widget's real HTML (#[Widget(lazy: true)]) —
    the inline script in pages/dashboard.blade.php fetches the real card from
    NexusController::widgetCard() and swaps it in after DOMContentLoaded.
--}}
<div class="nexus-widget-lazy flex min-h-30 items-center justify-center rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.02]"
    data-nexus-widget-lazy="{{ $meta->name }}">
    <div class="h-6 w-6 animate-spin rounded-full border-2 border-gray-200 border-t-brand-500 dark:border-gray-700" role="status">
        <span class="sr-only">{{ $meta->label }}</span>
    </div>
</div>
