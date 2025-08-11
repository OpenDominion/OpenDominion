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
                                        <i class="fa fa-clock-o"></i> {{ now()->longAbsoluteDiffForHumans($objective->start_date) }} ago
                                    @endif
                                </div>
                            </div>
                            @php
                                $realmScore = $raidCalculator->getObjectiveScore($objective, $selectedRealm);
                                $realmProgress = $raidCalculator->getObjectiveProgress($objective, $selectedRealm);
                                $realmCompleted = $raidCalculator->isObjectiveCompleted($objective, $selectedRealm);
                                $dominionContribution = $raidCalculator->getDominionContribution($objective, $selectedDominion);
                                $dominionPercentage = $realmScore > 0 ? ($dominionContribution / $realmScore) * 100 : 0;
                                $dominionProgressOfTotal = $objective->score_required > 0 ? ($dominionContribution / $objective->score_required) * 100 : 0;
                                $otherContributorsProgress = $realmProgress - $dominionProgressOfTotal;
                            @endphp
                            <div class="progress">
                                <div class="progress-bar progress-bar-{{ $realmCompleted ? 'success' : 'primary' }}" role="progressbar" style="width: {{ $otherContributorsProgress }}%">
                                    <span class="sr-only">{{ number_format($otherContributorsProgress, 1) }}% Complete (others)</span>
                                </div>
                                <div class="progress-bar progress-bar-info" role="progressbar" style="width: {{ $dominionProgressOfTotal }}%">
                                    <span class="sr-only">{{ number_format($dominionProgressOfTotal, 1) }}% Complete (you)</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <b>Realm Progress:</b> {{ number_format($realmScore) }} / {{ number_format($objective->score_required) }} ({{ number_format($realmProgress, 1) }}%)
                                    @if ($realmCompleted)
                                        <span class="label label-success">Completed!</span>
                                    @endif
                                    <br>
                                    <b>Your Contribution:</b> {{ number_format($dominionContribution) }} ({{ number_format($dominionPercentage, 1) }}% of realm)
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="{{ route('dominion.raids') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back to Raids
                                    </a>
                                    <a href="{{ route('dominion.raids.leaderboard', $objective) }}" class="btn btn-sm btn-info">
                                        <i class="fa fa-list"></i> View Leaderboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @php
                $tacticsByType = $objective->tactics->sortBy('sort_order')->groupBy('type');
            @endphp
            @foreach ($tacticsByType as $type => $tactics)
                <div class="row">
                    <div class="col-md-12">
                        @include("partials.dominion.raids.{$type}", ['tactics' => $tactics->sortBy('id')])
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
                        $recentContributions = $raidCalculator->getRecentContributions($objective, $selectedRealm, 10);
                    @endphp
                    @forelse($recentContributions as $contribution)
                        <div class="small" style="margin-bottom: 8px;">
                            <strong>{{ $contribution['dominion_name'] }}</strong>
                            <br/>
                            {{ ucwords(str_replace('_', ' ', $contribution['type'])) }} -
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
