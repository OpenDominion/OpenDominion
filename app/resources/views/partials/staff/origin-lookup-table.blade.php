{{-- Expects $results: OriginLookupReportService result rows, and $tableId --}}
@php($reportService = \OpenDominion\Services\Activity\OriginLookupReportService::class)
<table class="table table-sm table-hover mb-0" id="{{ $tableId }}">
    <thead>
        <tr>
            <th>IP Address</th>
            <th>Users</th>
            <th>Dominions</th>
            <th>Organization / ISP</th>
            <th class="text-center">Flags</th>
            <th class="text-center">Fraud Score</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($results as $result)
            <tr>
                <td>{{ $result['ip_address'] }}</td>
                <td>
                    @foreach ($result['users'] as $resultUser)
                        <a href="{{ route('staff.administrator.users.show', $resultUser['id']) }}" data-bs-toggle="tooltip" title="{{ $resultUser['display_name'] }}">Player{{ count($result['users']) > 1 ? ' ' . $loop->iteration : '' }}</a>@if (!$loop->last), @endif
                    @endforeach
                </td>
                <td>{{ implode(', ', $result['dominions']) }}</td>
                <td>
                    {{ $result['organization'] ?? $result['isp'] ?? '' }}
                    @if ($result['country'])
                        <span class="text-muted">({{ $result['country'] }})</span>
                    @endif
                    @if ($result['organization'] && $result['isp'] && $result['organization'] !== $result['isp'])
                        <div class="small text-muted">{{ $result['isp'] }}</div>
                    @endif
                </td>
                @if ($result['status'] === $reportService::STATUS_LOOKED_UP)
                    <td class="text-center" data-order="{{ ['danger' => 0, 'warning' => 1, 'secondary' => 2][$result['flags'][0]['severity'] ?? ''] ?? 3 }}">
                        @if ($result['has_anonymizer_data'])
                            @include('partials.staff.anonymizer-flags', ['flags' => $result['flags']])
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </td>
                    <td class="text-center" data-order="{{ $result['score'] ?? -1 }}">
                        @include('partials.staff.fraud-score', ['score' => $result['score']])
                    </td>
                @elseif ($result['status'] === $reportService::STATUS_FAILED)
                    <td class="text-center"><span class="badge text-bg-dark">Lookup failed</span></td>
                    <td></td>
                @else
                    <td class="text-center text-muted small">Not attempted</td>
                    <td></td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
