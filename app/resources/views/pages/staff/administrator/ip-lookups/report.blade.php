@extends('layouts.staff')

@section('page-header', 'Anti-Cheat')

@section('content')
    @php
        $batchService = \OpenDominion\Services\Activity\OriginLookupBatchService::class;
        $tierLabel = \OpenDominion\Services\Activity\OriginLookupSelectionService::TIER_LABELS[$report['tier']] ?? $report['tier'];
    @endphp

    <div class="card">
        <div class="card-header">
            <span class="card-title">Run Report - {{ $report['round_name'] }}</span>
        </div>
        <div class="card-body">
            <p class="text-muted small">
                {{ $tierLabel }} &middot; finished {{ $report['finished_at'] }} in {{ $report['duration_seconds'] }}s
            </p>

            @if ($report['stopped_early'])
                <div class="alert alert-danger py-2">
                    Stopped after {{ $batchService::MAX_CONSECUTIVE_FAILURES }} failed lookups in a row, so {{ $report['not_attempted'] }} {{ $report['not_attempted'] === 1 ? 'IP was' : 'IPs were' }} not attempted.
                    IPQS may be unreachable or out of credits. Check the application log for details.
                </div>
            @elseif ($report['failed'] > 0)
                <div class="alert alert-warning py-2">
                    {{ $report['failed'] }} {{ $report['failed'] === 1 ? 'lookup' : 'lookups' }} failed and will be picked again next run. Check the application log for details.
                </div>
            @endif

            <div class="row g-2 mb-3 text-center">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="fs-4 fw-bold">{{ $report['looked_up'] }}</div>
                        <div class="small text-muted">Looked up</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="fs-4 fw-bold {{ $report['failed'] > 0 ? 'text-danger' : '' }}">{{ $report['failed'] }}</div>
                        <div class="small text-muted">Failed</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="fs-4 fw-bold">{{ $report['not_attempted'] }}</div>
                        <div class="small text-muted">Not attempted</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2">
                        <div class="fs-4 fw-bold">{{ number_format($report['remaining']) }}</div>
                        <div class="small text-muted">Left in tier</div>
                    </div>
                </div>
            </div>

            @if ($report['looked_up'] > 0)
                <div class="mb-3">
                    @include('partials.staff.origin-lookup-findings', ['summary' => $report['summary']])
                </div>
            @endif

            <div class="table-responsive">
                @include('partials.staff.origin-lookup-table', ['results' => $report['results'], 'tableId' => 'ip-lookup-report-table'])
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap gap-2">
            <form action="{{ route('staff.administrator.ip-lookups') }}" method="post" onsubmit="var button = this.querySelector('button[type=submit]'); button.disabled = true; button.textContent = 'Looking up...';">
                @csrf
                <input type="hidden" name="round" value="{{ $report['round_id'] }}">
                <input type="hidden" name="tier" value="{{ $report['tier'] }}">
                <button type="submit" class="btn btn-primary" {{ $lookupsEnabled && $report['remaining'] > 0 ? '' : 'disabled' }}>Look Up Next {{ $batchService::BATCH_SIZE }}</button>
            </form>
            <a href="{{ route('staff.administrator.ip-lookups', ['round' => $report['round_id'], 'tier' => $report['tier']]) }}" class="btn btn-secondary">Back to IP Lookups</a>
        </div>
    </div>
@endsection
