@extends('layouts.master')

@section('page-header', 'Raids')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if (!$raids->isEmpty())
                @foreach ($raids as $raid)
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-castle-flag"></i> {{ $raid->name }}</h3>
                            <div class="pull-right">
                                {!! $raidHelper->getStatusLabel($raid->status) !!}
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row form-group">
                                        <div class="col-md-12">
                                            {!! $raid->description !!}
                                        </div>
                                    </div>
                                    <div class="row form-group">
                                        <div class="col-sm-12 col-md-4">
                                            @if (!$raid->hasStarted())
                                                <i class="fa fa-clock-o"></i> Starts in {{ $raid->timeUntilStart() }}
                                            @elseif ($raid->isActive())
                                                <i class="fa fa-clock-o"></i> Ends in {{ $raid->timeUntilEnd() }}
                                            @else
                                                <i class="fa fa-clock-o"></i> Ended {{ now()->longAbsoluteDiffForHumans($raid->start_date) }} ago
                                            @endif
                                            <br/>
                                            <i class="fa fa-calendar"></i>
                                            Day {{ $selectedDominion->round->daysInRound($raid->start_date) }} - {{ $selectedDominion->round->daysInRound($raid->end_date) }}
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <strong>Participation Rewards:</strong><br/>
                                            {{ number_format($raid->reward_amount) }} {{ dominion_attr_display($raid->reward_resource, $raid->reward_amount) }}
                                        </div>
                                        <div class="col-sm-6 col-md-4">
                                            <strong>Completion Bonus:</strong><br/>
                                            {{ number_format($raid->completion_reward_amount) }} {{ dominion_attr_display($raid->completion_reward_resource, $raid->completion_reward_amount) }}
                                            <span class="text-muted">(per player)</span>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-condensed">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>Objective</th>
                                                    <th>Description</th>
                                                    <th>Duration</th>
                                                    <th>Score Required</th>
                                                    <th>Realm Progress</th>
                                                    <th>Tactics</th>
                                                </tr>
                                            </thead>
                                            @foreach ($raid->objectives->sortBy('order') as $objective)
                                                @php
                                                    $realmScore = $raidCalculator->getObjectiveScore($objective, $selectedDominion->realm);
                                                    $realmProgress = $raidCalculator->getObjectiveProgress($objective, $selectedDominion->realm);
                                                    $realmCompleted = $raidCalculator->isObjectiveCompleted($objective, $selectedDominion->realm);
                                                @endphp
                                                <tr class="{{ $objective->isActive() ? 'info' : null}}">
                                                    <td>{{ $objective->order }}</td>
                                                    <td>
                                                        <a href="{{ route('dominion.raids.objective', [$objective->id]) }}">
                                                            {{ $objective->name }}
                                                        </a>
                                                        @if ($realmCompleted)
                                                            <span class="label label-success">Completed</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $objective->description }}</td>
                                                    <td>{{ $objective->start_date->diffInHours($objective->end_date) }} hours</td>
                                                    <td>{{ number_format($objective->score_required) }}</td>
                                                    <td>{{ number_format($realmScore) }} ({{ number_format($realmProgress, 1) }}%)</td>
                                                    <td>
                                                        @foreach ($objective->tactics->unique('type') as $tactic)
                                                            <div class="label label-primary">{{ ucwords($tactic->type) }}</div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 text-right">
                                    <a href="{{ route('dominion.raids.leaderboard', $raid) }}" class="btn btn-sm btn-info">
                                        <i class="fa fa-list"></i> Raid Leaderboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-castle-flag"></i> Raids</h3>
                    </div>
                    <div class="box-body">
                        There are currently no raids scheduled for this round.
                    </div>
                </div>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <h4>Participation Rewards</h4>
                    <p class="small">Rewards are earned based on your contribution.</p>
                    <ul class="small">
                        <li><strong>Realm Share:</strong> Each realm earns up to 15% of the total based on their contribution relative to all other realms</li>
                        <li><strong>Player Share:</strong> Each player earns a portion of the realm's share up to 15% of the realm's total score</li>
                        <li><strong>Distribution:</strong> Any remaining resources are shared equally among all participants</li>
                    </ul>
                    <h4>Completion Bonus</h4>
                    <p class="small">Each player receives a completion bonus based on the number of objectives completed by their realm.</p>
                    <ul class="small">
                        <li><strong>Scaled Rewards:</strong> Rewards are proportional to the number of objectives completed</li>
                        <li><strong>Per Player:</strong> Each participating player gets the full amount regardless of their individual score</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-styles')
    <style>
        .rewards-info {
            background: #f8f9fa;
            border-left: 3px solid #007bff;
            border-radius: 3px;
            padding: 6px 8px;
            margin-top: 4px;
        }
        .rewards-info .small {
            color: #495057;
            line-height: 1.3;
            margin-top: 2px;
        }
        .rewards-info .fa {
            color: #007bff;
            margin-right: 4px;
            width: 12px;
        }
        .rewards-info .fa-trophy {
            color: #ffc107;
        }
        .rewards-info .fa-star {
            color: #28a745;
        }
        .rewards-info .text-muted {
            font-size: 11px;
        }
    </style>
@endpush
