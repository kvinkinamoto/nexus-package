<div class="hello-world-widget text-center py-4">
    <h3>{{ $message }}</h3>
    @if($show_date)
        <p class="text-muted">{{ now()->format('Y-m-d') }}</p>
    @endif
</div>
