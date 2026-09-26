{{-- Expects $score: IPQS fraud score (float|null) --}}
@if ($score === null)
    <span class="text-muted">&mdash;</span>
@else
    @php
        $fraudScoreClass = match (true) {
            $score >= \OpenDominion\Services\Activity\OriginLookupReportService::HIGH_RISK_SCORE => 'text-bg-danger',
            $score >= \OpenDominion\Services\Activity\OriginLookupReportService::SUSPICIOUS_SCORE => 'text-bg-warning',
            default => 'text-bg-secondary',
        };
    @endphp
    <span class="badge {{ $fraudScoreClass }}">{{ round($score) }}</span>
@endif
