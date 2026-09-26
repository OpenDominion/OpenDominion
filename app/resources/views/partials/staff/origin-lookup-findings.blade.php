{{-- Expects $summary: OriginLookupReportService summary array --}}
<dl class="row mb-0">
    <dt class="col-sm-3">Anonymizers</dt>
    <dd class="col-sm-9">
        @foreach ($summary['flag_counts'] as $flagCount)
            <span class="badge text-bg-{{ $flagCount['severity'] }}">{{ $flagCount['label'] }} &times; {{ number_format($flagCount['count']) }}</span>
        @endforeach
        @if ($summary['clean'] > 0)
            <span class="badge text-bg-success">Clean &times; {{ number_format($summary['clean']) }}</span>
        @endif
        @if (empty($summary['flag_counts']) && $summary['clean'] === 0)
            <span class="text-muted">&mdash;</span>
        @endif
    </dd>
    <dt class="col-sm-3">Fraud score</dt>
    <dd class="col-sm-9 mb-0">
        <span class="badge text-bg-danger">{{ \OpenDominion\Services\Activity\OriginLookupReportService::HIGH_RISK_SCORE }}+ &times; {{ number_format($summary['high_risk']) }}</span>
        <span class="badge text-bg-warning">{{ \OpenDominion\Services\Activity\OriginLookupReportService::SUSPICIOUS_SCORE }}&ndash;{{ \OpenDominion\Services\Activity\OriginLookupReportService::HIGH_RISK_SCORE - 1 }} &times; {{ number_format($summary['suspicious']) }}</span>
    </dd>
</dl>
