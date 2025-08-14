@extends('layouts.master')

@section('page-header', 'Raid Leaderboard')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-castle-flag"></i> {{ $raid->name }} - Overall Leaderboard
                    </h3>
                    <div class="pull-right">
                        {!! $raidHelper->getStatusLabel($raid->status) !!}
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row form-group">
                                <div class="col-md-9">
                                    {!! $raid->description !!}
                                </div>
                                <div class="col-md-3 text-right">
                                    @if (!$raid->hasStarted())
                                        <i class="fa fa-clock-o"></i> Starts in {{ $raid->timeUntilStart() }}
                                    @elseif ($raid->isActive())
                                        <i class="fa fa-clock-o"></i> Ends in {{ $raid->timeUntilEnd() }}
                                    @else
                                        <i class="fa fa-clock-o"></i> Ended {{ now()->longAbsoluteDiffForHumans($raid->start_date) }} ago
                                    @endif
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-blue"><i class="fa fa-trophy"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Objectives</span>
                                            <span class="info-box-number">{{ $raid->objectives->count() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-yellow"><i class="fa fa-gift"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Participation Pool</span>
                                            <span class="info-box-number">{{ number_format($raid->reward_amount) }} {{ dominion_attr_display($raid->reward_resource, $raid->reward_amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-green"><i class="fa fa-star"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Completion Bonus</span>
                                            <span class="info-box-number">{{ number_format($raid->completion_reward_amount) }} {{ dominion_attr_display($raid->completion_reward_resource) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @php
                                $grandTotalScore = collect($leaderboard)->sum('total_score');
                            @endphp
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Realm</th>
                                            <th>Total Score</th>
                                            <th>Realm Share</th>
                                            <th>Objectives</th>
                                            <th>Completion Bonus</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($leaderboard as $index => $entry)
                                            <tr>
                                                <td>
                                                    @if ($index == 0)
                                                        <i class="fa fa-trophy text-orange"></i>
                                                    @elseif ($index == 1)
                                                        <i class="fa fa-trophy text-grey"></i>
                                                    @elseif ($index == 2)
                                                        <i class="fa fa-trophy text-warning"></i>
                                                    @else
                                                        {{ $index + 1 }}
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $entry['realm_name'] }} (#{{ $entry['realm_number'] }})
                                                    @if ($entry['realm_id'] == $selectedDominion->realm_id)
                                                        <span class="label label-info">Your Realm</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ number_format($entry['total_score']) }}
                                                    @if ($grandTotalScore > 0)
                                                        ({{ number_format(($entry['total_score'] / $grandTotalScore) * 100, 1) }}%)
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ number_format($entry['estimated_participation_reward']) }}
                                                    {{ dominion_attr_display($raid->reward_resource, $entry['estimated_participation_reward']) }}
                                                </td>
                                                <td>
                                                    {{ $entry['completed_objectives'] }} / {{ $entry['total_objectives'] }}
                                                </td>
                                                <td>
                                                    {{ number_format($entry['completed_objectives'] / $entry['total_objectives'] * $raid->completion_reward_amount) }}
                                                    {{ dominion_attr_display($raid->completion_reward_resource) }} <span class="small">(per player)</span>
                                                </td>
                                                <td>
                                                    @if ($entry['fully_completed'])
                                                        {!! $raidHelper->getStatusLabel("Completed", true) !!}
                                                    @elseif ($entry['completed_objectives'] > 0)
                                                        {!! $raidHelper->getStatusLabel("Partial", true) !!}
                                                    @else
                                                        {!! $raidHelper->getStatusLabel($raid->status, true) !!}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <a href="{{ route('dominion.raids') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back to Raids
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (!empty($playerBreakdown))
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Realm Participation</h3>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Player</th>
                                        <th>Score</th>
                                        <th>Estimated Reward</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($playerBreakdown as $player)
                                        <tr>
                                            <td>
                                                {{ $player['dominion_name'] }}
                                                @if($player['dominion_id'] == $selectedDominion->id)
                                                    <span class="label label-info">You</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ number_format($player['total_score']) }}
                                                ({{ number_format($player['percentage_of_realm'], 1) }}%)
                                            </td>
                                            <td>
                                                {{ number_format($player['estimated_reward']) }}
                                                {{ dominion_attr_display($raid->reward_resource, $player['estimated_reward']) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Your Realm Performance</h3>
                </div>
                <div class="box-body">
                    @php
                        $yourRealmData = collect($leaderboard)->firstWhere('realm_id', $selectedDominion->realm_id);
                        $yourRank = $yourRealmData ? array_search($yourRealmData, $leaderboard) + 1 : 'N/A';
                    @endphp

                    @if ($yourRealmData)
                        <p><strong>Rank:</strong> {{ $yourRank }}</p>
                        <p><strong>Total Score:</strong> {{ number_format($yourRealmData['total_score']) }}</p>
                        <p><strong>Realm Share:</strong> {{ number_format($yourRealmData['estimated_participation_reward']) }} {{ dominion_attr_display($raid->reward_resource, $yourRealmData['estimated_participation_reward']) }}</p>
                        <p><strong>Objectives:</strong> {{ $yourRealmData['completed_objectives'] }} / {{ $yourRealmData['total_objectives'] }}</p>
                        <p><strong>Completion:</strong> {{ number_format($yourRealmData['completion_percentage'], 1) }}%</p>
                        <p><strong>Completion Bonus:</strong> {{ number_format($entry['completed_objectives'] / $entry['total_objectives'] * $raid->completion_reward_amount) }} {{ dominion_attr_display($raid->completion_reward_resource) }}</p>
                        <p><strong>Status:</strong>
                            @if ($yourRealmData['fully_completed'])
                                Complete
                            @elseif ($yourRealmData['completed_objectives'] > 0)
                                Partial
                            @else
                                {{ $raid->status }}
                            @endif
                        </p>
                    @else
                        <p><em>Your realm has not participated in this raid yet.</em></p>
                    @endif
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Objectives</h3>
                </div>
                <div class="box-body">
                    @foreach($raid->objectives->sortBy('order') as $objective)
                        @php
                            $isCompleted = $raidCalculator->isObjectiveCompleted($objective, $selectedDominion->realm);
                        @endphp
                        <div style="margin-bottom: 8px;">
                            <a href="{{ route('dominion.raids.objective', $objective) }}">
                                {{ $objective->name }}
                            </a>
                            {!! $raidHelper->getStatusLabel($objective->status) !!}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
@endsection
