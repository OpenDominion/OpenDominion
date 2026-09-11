@extends('layouts.staff')

@section('page-header', 'Anti-Cheat')

@section('content')
    @php
        $batchService = \OpenDominion\Services\Activity\OriginLookupBatchService::class;
        $lookupTierLabels = \OpenDominion\Services\Activity\OriginLookupSelectionService::TIER_LABELS;
    @endphp

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                IP Lookups - {{ $round->name }}
            </span>
            <select id="round-select" class="form-select float-end">
                @foreach ($rounds as $roundOption)
                    <option value="{{ $roundOption->id }}" {{ $roundOption->id == $round->id ? 'selected' : null }}>
                        {{ $roundOption->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="card-body">
            <p>
                {{ number_format($unlookedIpCount) }} {{ $unlookedIpCount === 1 ? 'IP' : 'IPs' }} used in this round {{ $unlookedIpCount === 1 ? 'has' : 'have' }} not been looked up.
                Each run looks up the next {{ $batchService::BATCH_SIZE }} IPs in the chosen tier, {{ $batchService::SPACING_SECONDS }} second apart,
                and stops early after {{ $batchService::MAX_CONSECUTIVE_FAILURES }} failures in a row. IPs that were already looked up are never counted or repeated.
            </p>
            <form action="{{ route('staff.administrator.ip-lookups') }}" method="post" onsubmit="var button = this.querySelector('button[type=submit]'); button.disabled = true; button.textContent = 'Looking up...';">
                @csrf
                <input type="hidden" name="round" value="{{ $round->id }}">
                <div class="table-responsive">
                    <table class="table table-sm w-auto">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Tier</th>
                                <th class="text-end">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lookupTierLabels as $tier => $tierLabel)
                                <tr>
                                    <td>
                                        <input type="radio" class="form-check-input" id="lookup-tier-{{ $tier }}" name="tier" value="{{ $tier }}" {{ $tier === $selectedTier ? 'checked' : '' }} {{ $availableTierCounts[$tier] > 0 ? '' : 'disabled' }}>
                                    </td>
                                    <td>
                                        <label for="lookup-tier-{{ $tier }}" class="mb-0">{{ $tierLabel }}</label>
                                    </td>
                                    <td class="text-end">{{ number_format($availableTierCounts[$tier]) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary" {{ $lookupsEnabled && array_sum($availableTierCounts) > 0 ? '' : 'disabled' }}>Look Up Next {{ $batchService::BATCH_SIZE }}</button>
                @if (session()->has(\OpenDominion\Http\Controllers\Staff\Administrator\OriginLookupController::REPORT_SESSION_KEY))
                    <a href="{{ route('staff.administrator.ip-lookups.report') }}" class="btn btn-link">View last run report</a>
                @endif
                @if (!$lookupsEnabled)
                    <p class="form-text mb-0">IP lookups unavailable: no IPQS API key configured</p>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                Findings - {{ number_format($roundReport['summary']['looked_up']) }} looked-up {{ $roundReport['summary']['looked_up'] === 1 ? 'IP' : 'IPs' }}
            </span>
        </div>
        <div class="card-body">
            @include('partials.staff.origin-lookup-findings', ['summary' => $roundReport['summary']])
        </div>
        <div class="card-body table-responsive">
            @include('partials.staff.origin-lookup-table', ['results' => $roundReport['results'], 'tableId' => 'ip-lookups-table'])
        </div>
    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap5.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap5.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#ip-lookups-table').DataTable({
                "dom": '<"top"fi<"clear">>rt<"bottom"ilp<"clear">>',
                'paging': false,
                'order': []
            });

            $('#round-select').select2({ width: '225px' }).change(function() {
                var selectedRound = $(this).val();
                window.location.href = "{!! route('staff.administrator.ip-lookups') !!}/?round=" + selectedRound;
            });
            $('#round-select + .select2-container').addClass('float-end');
        })(jQuery);
    </script>
@endpush
