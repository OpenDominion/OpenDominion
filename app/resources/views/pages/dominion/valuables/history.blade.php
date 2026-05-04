@extends ('layouts.master')

@section('page-header', 'Valuables History')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-gem"></i> Round History</span>
                </div>

                @if ($history->isEmpty())
                    <div class="card-body">
                        <p class="text-center text-muted my-3">No completed valuables in this round yet.</p>
                    </div>
                @else
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Completed</th>
                                <th>Valuable</th>
                                <th>Target</th>
                                <th>Result</th>
                                <th>Sale Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $valuable)
                                @php
                                    $statusLabel = match ($valuable->status) {
                                        \OpenDominion\Models\Valuable::STATUS_SOLD => 'Sold',
                                        \OpenDominion\Models\Valuable::STATUS_EXPIRED => 'Expired',
                                        \OpenDominion\Models\Valuable::STATUS_FAILED => 'Theft Failed',
                                        default => ucfirst($valuable->status),
                                    };
                                    $resultLabel = $valuable->status === \OpenDominion\Models\Valuable::STATUS_SOLD ? 'Success' : 'Failed';
                                    $resultClass = $valuable->status === \OpenDominion\Models\Valuable::STATUS_SOLD ? 'text-success' : 'text-danger';
                                @endphp
                                <tr>
                                    <td>{{ $valuable->updated_at->diffForHumans() }}</td>
                                    <td>
                                        <strong>{{ $valuable->name }}</strong><br>
                                        <small class="text-muted">{{ ucfirst($valuable->rarity) }} &middot; {{ ucfirst($valuable->type) }}</small>
                                    </td>
                                    <td>{{ optional($valuable->targetDominion)->name ?? '—' }}</td>
                                    <td class="{{ $resultClass }}">{{ $resultLabel }}</td>
                                    <td>{{ $valuable->sold_price !== null ? number_format($valuable->sold_price) . 'p' : '—' }}</td>
                                    <td>{{ $statusLabel }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Round Summary</span>
                </div>
                <div class="card-body">
                    <p>Total attempts: <strong>{{ number_format($stats['totalAttempts']) }}</strong></p>
                    <p>Successful thefts: <strong class="text-success">{{ number_format($stats['successfulThefts']) }}</strong></p>
                    <p>Failed thefts: <strong class="text-danger">{{ number_format($stats['failedThefts']) }}</strong></p>
                    <p>Sold: <strong>{{ number_format($stats['sold']) }}</strong></p>
                    <p>Expired: <strong>{{ number_format($stats['expired']) }}</strong></p>
                    <p>Platinum earned: <strong>{{ number_format($stats['totalPlatinumEarned']) }}p</strong></p>
                    <p>Success rate: <strong>{{ number_format($stats['successRate'], 1) }}%</strong></p>
                    <p><a href="{{ route('dominion.espionage') }}">&laquo; Back to Espionage</a></p>
                </div>
            </div>
        </div>

    </div>
@endsection
