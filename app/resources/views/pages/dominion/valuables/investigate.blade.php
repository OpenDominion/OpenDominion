@extends ('layouts.master')

@section('page-header', 'Investigate Valuable')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title">
                        <i class="ra ra-gem"></i>
                        Investigate: <span class="{{ $valuablesHelper->getRarityClass($valuable->rarity) }}">{{ $valuable->name }}</span>
                    </span>
                </div>
                <div class="card-body">
                    <p>
                        <strong>{{ ucfirst($valuable->rarity) }}</strong> &middot;
                        <strong>{{ ucfirst($valuable->type) }}</strong> &middot;
                        held by <a href="{{ route('dominion.op-center.show', $valuable->target_dominion_id) }}">{{ $valuable->targetDominion->name }}</a>
                    </p>
                    <p>Pick a duration. Faster heists need more spies; slower heists cost more strength over time.<br/>Each investigation's spies are committed for the entire duration of the investigation.</p>

                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Duration</th>
                                <th>Spies Required</th>
                                <th>Total Spy Strength</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($durationOptions as $option)
                                <tr class="{{ $option['disabled'] ? 'text-muted' : '' }}">
                                    <td>{{ $option['hours'] }} hours</td>
                                    <td>{{ number_format($option['spiesNeeded']) }}</td>
                                    <td>{{ number_format($option['totalStrengthCost'], 0) }}%</td>
                                    <td class="text-end">
                                        <form action="{{ route('dominion.valuables.investigate', $valuable->id) }}" method="post" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="hours" value="{{ $option['hours'] }}">
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $option['disabled'] ? 'disabled' : '' }}>
                                                Select
                                            </button>
                                        </form>
                                        @if ($option['disabled'])
                                            <small class="d-block text-muted">{{ $option['disabledReason'] }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Investigation Details</span>
                </div>
                <div class="card-body">
                    <p>Required spy-hours: <strong>{{ number_format($requiredSpyHours) }}</strong></p>
                    <p>Available spies: <strong>{{ number_format($availableSpies) }}</strong></p>
                    <p>Current spy-strength regen: <strong>{{ sprintf('%+.1f', $currentRegen) }}%/hr</strong></p>
                    <p>Active investigations: <strong>{{ $activeInvestigations }}</strong></p>
                    @if ($currentRegen - \OpenDominion\Helpers\ValuablesHelper::SPY_STRENGTH_PER_INVESTIGATION <= 0)
                        <p class="text-danger">
                            Adding another investigation would zero out your spy regen. Wait for an existing investigation to complete or cancel one first.
                        </p>
                    @endif
                    <p><a href="{{ route('dominion.valuables') }}">&laquo; Back to Valuables</a></p>
                </div>
            </div>
        </div>

    </div>
@endsection
