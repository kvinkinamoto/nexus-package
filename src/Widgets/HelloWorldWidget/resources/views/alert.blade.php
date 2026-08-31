<div class="alert alert-success hello-world-widget" role="alert">
    <h4 class="alert-heading">{{ $message }}</h4>
    @if($show_date)
        <hr>
        <p class="mb-0">{{ now()->format('Y-m-d') }}</p>
    @endif
</div>
