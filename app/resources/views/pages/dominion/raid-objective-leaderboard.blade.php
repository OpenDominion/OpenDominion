@extends('layouts.master')

@section('page-header', 'Raid Leaderboard')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-castle-flag"></i> {{ $objective->raid->name }}: {{ $objective->name }}
                        - Leaderboard
                    </h3>
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
                                        <i class="fa fa-clock-o"></i> Ended {{ now()->longAbsoluteDiffForHumans($objective->start_date) }} ago
                                    @endif
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-blue"><i class="fa fa-trophy"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Score Required</span>
                                            <span class="info-box-number">{{ number_format($objective->score_required) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-green"><i class="fa fa-globe"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total Global Score</span>
                                            <span class="info-box-number">{{ number_format($totalScore) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Realm</th>
                                            <th>Score</th>
                                            <th>Progress</th>
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
                                                    @if ($entry['realm_id'] == $selectedRealm->id)
                                                        <span class="label label-info">Your Realm</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ number_format($entry['total_score']) }}
                                                    @if ($totalScore > 0)
                                                        ({{ number_format($entry['total_score'] / $totalScore * 100) }}%)
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar progress-bar-{{ $entry['completed'] ? 'success' : 'primary' }}"
                                                             role="progressbar"
                                                             style="width: {{ $entry['progress'] }}%">
                                                            <span class="sr-only">{{ number_format($entry['progress'], 1) }}% Complete</span>
                                                        </div>
                                                    </div>
                                                    {{ number_format($entry['progress'], 1) }}%
                                                </td>
                                                <td>
                                                    @if ($entry['completed'])
                                                        {!! $raidHelper->getStatusLabel('Completed', true) !!}
                                                    @else
                                                        {!! $raidHelper->getStatusLabel($objective->status, true) !!}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <a href="{{ route('dominion.raids.objective', $objective) }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back to Objective
                                    </a>
                                    <a href="{{ route('dominion.raids') }}" class="btn btn-info btn-sm">
                                        <i class="ra ra-castle-flag"></i> Raids
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Your Realm Performance</h3>
                </div>
                <div class="box-body">
                    @php
                        $yourRealmData = collect($leaderboard)->firstWhere('realm_id', $selectedRealm->id);
                        $yourRank = $yourRealmData ? array_search($yourRealmData, $leaderboard) + 1 : 'N/A';
                        $topContributors = $raidCalculator->getTopContributorsInRealm($objective, $selectedRealm, 10);
                    @endphp

                    @if ($yourRealmData)
                        <p><strong>Rank:</strong> {{ $yourRank }}</p>
                        <p><strong>Score:</strong> {{ number_format($yourRealmData['total_score']) }}</p>
                        <p><strong>Progress:</strong> {{ number_format($yourRealmData['progress'], 1) }}%</p>
                        <p><strong>Status:</strong> {{ $yourRealmData['completed'] ? 'Completed' : $objective->status }}</p>
                    @else
                        <p><em>Your realm has not contributed to this objective yet.</em></p>
                    @endif
                </div>
            </div>
            @if (!empty($topContributors))
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Top Contributors</h3>
                    </div>
                    <div class="box-body">
                        <ol>
                            @foreach ($topContributors as $contributor)
                                <li>
                                    {{ $contributor['dominion_name'] }} - {{ number_format($contributor['total_score']) }}
                                    ({{ number_format($contributor['total_score'] / $yourRealmData['total_score'] * 100) }}%}
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection