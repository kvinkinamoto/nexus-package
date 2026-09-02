{{--
    Built-in fallback presentation for a widget that implements ProvidesMetric
    but not RendersHtml — see AdminDashboardRenderer::renderMetricCard().
    Sparkline/trend polish is Phase 5.4; this is deliberately the bare card.
--}}
<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.02]">
    <div class="flex items-center justify-between">
        <div>
            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">{{ $metric->label }}</p>
            <h3 class="text-title-sm font-semibold text-gray-800 dark:text-white/90">
                {{ $metric->value }}@if($metric->unit)<small class="text-sm font-normal text-gray-400">{{ $metric->unit }}</small>@endif
            </h3>
            @if($metric->deltaPercent !== null)
                <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                    {{ $metric->direction === 'down' ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400' : ($metric->direction === 'up' ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400') }}">
                    {{ $metric->deltaPercent > 0 ? '+' : '' }}{{ $metric->deltaPercent }}%
                </span>
            @endif
        </div>
        @if($metric->icon)
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                <i class="{{ $metric->icon }} text-xl"></i>
            </div>
        @endif
    </div>
</div>
