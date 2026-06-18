<div class="d-none d-print-block text-center mb-4">
    <h3 class="mb-1">SXpress Logistics</h3>
    <h5 class="mb-1">{{ $reportTitle ?? 'Financial Report' }}</h5>
    <p class="mb-1">{{ $reportPeriod ?? '' }}</p>
    <small class="text-muted">Generated on: {{ now()->format('d M Y, h:i A') }}</small>
    <hr>
</div>
