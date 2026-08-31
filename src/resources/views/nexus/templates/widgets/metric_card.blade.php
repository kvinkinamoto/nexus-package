{{--
    Built-in fallback presentation for a widget that implements ProvidesMetric
    but not RendersHtml — see AdminDashboardRenderer::renderMetricCard().
    Sparkline/trend polish is Phase 5.4; this is deliberately the bare card.
--}}
<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="text-muted mb-1">{{ $metric->label }}</p>
                <h3 class="mb-0">
                    {{ $metric->value }}@if($metric->unit)<small class="text-muted">{{ $metric->unit }}</small>@endif
                </h3>
                @if($metric->deltaPercent !== null)
                    <span class="badge {{ $metric->direction === 'down' ? 'bg-danger' : ($metric->direction === 'up' ? 'bg-success' : 'bg-secondary') }}">
                        {{ $metric->deltaPercent > 0 ? '+' : '' }}{{ $metric->deltaPercent }}%
                    </span>
                @endif
            </div>
            @if($metric->icon)
                <div class="avatar-sm">
                    <span class="avatar-title bg-light text-primary rounded fs-3">
                        <i class="{{ $metric->icon }}"></i>
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
