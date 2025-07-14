@extends('layouts.master')

@section('page-header', 'Raids')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle-flag"></i> {{ $objective->raid->name }}: {{ $objective->name }}</h3>
                    <div class="pull-right">
                        <span class="badge">
                            <i class="ra ra-hourglass"></i> {{ $objective->status }}
                        </span>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row form-group">
                                <div class="col-md-9">
                                    {{ $objective->description }}
                                </div>
                                <div class="col-md-3 text-right">
                                    @if (!$objective->hasStarted())
                                        <i class="fa fa-clock-o"></i> Starts in {{ $objective->timeUntilStart() }}
                                    @elseif ($objective->isActive())
                                        <i class="fa fa-clock-o"></i> Ends in {{ $objective->timeUntilEnd() }}
                                    @else
                                        <i class="fa fa-clock-o"></i> Completed
                                    @endif
                                </div>
                            </div>
                            @php
                                $totalScore = $raidCalculator->getObjectiveScore($objective);
                                $progress = $raidCalculator->getObjectiveProgress($objective);
                                $dominionContribution = $raidCalculator->getDominionContribution($objective, $selectedDominion);
                                $dominionPercentage = $raidCalculator->getDominionContributionPercentage($objective, $selectedDominion);
                                $realmContribution = $raidCalculator->getRealmContribution($objective, $selectedDominion->realm);
                                $realmPercentage = $raidCalculator->getRealmContributionPercentage($objective, $selectedDominion->realm);
                            @endphp
                            <div class="progress">
                                <div class="progress-bar progress-bar-green" role="progressbar" aria-valuenow="{{ $realmPercentage }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $realmPercentage }}%">
                                    <span class="sr-only">{{ number_format($realmPercentage, 1) }}% Complete (realm)</span>
                                </div>
                                <div class="progress-bar progress-bar-blue" role="progressbar" aria-valuenow="{{ $dominionPercentage }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $dominionPercentage }}%">
                                    <span class="sr-only">{{ number_format($dominionPercentage, 1) }}% Complete (you)</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <b>Progress:</b> {{ number_format($totalScore) }} / {{ number_format($objective->score_required) }} ({{ number_format($progress, 1) }}%)
                                </div>
                                <div class="col-md-6">
                                    <b>Your Contribution:</b> {{ number_format($dominionContribution) }} / {{ number_format($totalScore) }} ({{ number_format($dominionPercentage, 1) }}%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @foreach ($objective->tactics->sortBy('sort_order') as $tactic)
                <div class="row">
                    <div class="col-md-12">
                        @include("partials.dominion.raids.{$tactic->type}")
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Raids are great</p>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Actions</h3>
                </div>
                <div class="box-body">
                    @php
                        $recentContributions = $raidCalculator->getRecentContributions($objective, 5);
                    @endphp
                    @forelse($recentContributions as $contribution)
                        <div class="small" style="margin-bottom: 8px;">
                            <strong>{{ $contribution['dominion_name'] }}</strong> ({{ $contribution['realm_name'] }})
                            <br/>
                            {{ ucwords(str_replace('_', ' ', $contribution['type'])) }}
                            <span class="text-muted">{{ $contribution['created_at']->diffForHumans() }}</span>
                            <div class="pull-right text-success">+{{ number_format($contribution['score']) }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No recent contributions.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            console.log('we need this');
        })(jQuery);
    </script>
@endpush
