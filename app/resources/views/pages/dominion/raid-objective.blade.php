@extends('layouts.master')

@section('page-header', 'Raids')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-castle-flag"></i> {{ $objective->raid->name }}: {{ $objective->name }}</h3>
                    <div class="pull-right">
                        {!! $raidHelper->getStatusLabel($objective->status) !!}
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
                                        <i class="fa fa-clock-o"></i> Ended {{ now()->longAbsoluteDiffForHumans($objective->end_date) }} ago
                                    @endif
                                </div>
                            </div>
                            @php
                                $realmScore = $raidCalculator->getObjectiveScore($objective, $selectedDominion->realm);
                                $realmProgress = $raidCalculator->getObjectiveProgress($objective, $selectedDominion->realm);
                                $realmCompleted = $raidCalculator->isObjectiveCompleted($objective, $selectedDominion->realm);
                                $dominionContribution = $raidCalculator->getDominionContribution($objective, $selectedDominion);
                                $dominionPercentage = $realmScore > 0 ? ($dominionContribution / $realmScore) * 100 : 0;
                                $dominionProgressOfTotal = $objective->score_required > 0 ? ($dominionContribution / $objective->score_required) * 100 : 0;
                                $otherContributorsProgress = $realmProgress - $dominionProgressOfTotal;
                            @endphp
                            @if ($realmCompleted)
                                <div class="alert alert-success">
                                    Your realm has completed this objective! Everyone who contributes to the raid will be awarded {{ dominion_attr_display($objective->raid->completion_reward_resource, $objective->raid->completion_reward_amount) }}.
                                    <br/>You can still increase your score to earn a higher share of the spoils ({{ number_format($objective->raid->reward_amount) }} {{ dominion_attr_display($objective->raid->reward_resource, $objective->raid->reward_amount) }} divided between all realms).
                                </div>
                            @endif
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
                                    <br>
                                    @php
                                        $activityMultiplier = $raidCalculator->getRealmActivityMultiplier($selectedDominion, $objective->raid);
                                    @endphp
                                    <b>Realm Size Score Multiplier:</b>
                                    @if ($activityMultiplier < 1.0)
                                        <span class="text-red">{{ number_format(($activityMultiplier - 1) * 100, 2) }}%</span>
                                    @elseif ($activityMultiplier > 1.0)
                                        <span class="text-green">+{{ number_format(($activityMultiplier - 1) * 100, 2) }}%</span>
                                    @else
                                        <span class="text-muted">0%</span>
                                    @endif
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="{{ route('dominion.raids') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back to Raids
                                    </a>
                                    <a href="{{ route('dominion.raids.objective.leaderboard', $objective) }}" class="btn btn-sm btn-info">
                                        <i class="fa fa-list"></i> Objective Leaderboard
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
                    <h3 class="box-title">Objective Details</h3>
                </div>
                <div class="box-body">
                    <p>
                        <strong>Score Required:</strong><br>
                        {{ number_format($objective->score_required) }} points
                    </p>
                    <p>
                        <strong>Available Tactics:</strong><br>
                        @php $tacticsByType = $objective->tactics->unique('type'); @endphp
                        @foreach($tacticsByType as $tactic)
                            <span class="label label-primary">{{ ucwords($tactic->type) }}</span>
                        @endforeach
                    </p>
                    <p>
                        <strong>Duration:</strong><br>
                        {{ $objective->start_date->diffInHours($objective->end_date) }} hours
                    </p>
                    @if($objective->isActive())
                        <p>
                            <strong>Time Remaining:</strong><br>
                            <i class="fa fa-clock-o"></i> {{ $objective->timeUntilEnd() }}
                        </p>
                    @endif
                    <div class="form-group">
                        Rewards are distributed at the raid level across all objectives at the end of the raid.
                    </div>
                    <div>
                        <a href="{{ route('dominion.raids.leaderboard', $objective->raid) }}" class="btn btn-sm btn-info">
                            <i class="fa fa-trophy"></i> Raid Leaderboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Recent Actions</h3>
                </div>
                <div class="box-body">
                    @php
                        $recentContributions = $raidCalculator->getRecentContributions($objective, $selectedDominion->realm, 10);
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
