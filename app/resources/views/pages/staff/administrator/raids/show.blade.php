@extends('layouts.staff')

@section('page-header', 'Raid Details')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $raid->name }}</h3>
                    <div class="pull-right">
                        <a href="{{ route('staff.administrator.raids.edit', $raid) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('staff.administrator.raids.delete', $raid) }}" class="btn btn-danger btn-sm">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                        <a href="{{ route('staff.administrator.raids.index', ['round' => $raid->round_id]) }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <tr>
                                    <th width="200">Round</th>
                                    <td>{{ $raid->round->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{!! $raidHelper->getStatusLabel($raid->status) !!}</td>
                                </tr>
                                <tr>
                                    <th>Start Date</th>
                                    <td>Day {{ $raid->round->daysInRound($raid->start_date) }} ({{ $raid->start_date->format('M d, Y H:i') }})</td>
                                </tr>
                                <tr>
                                    <th>End Date</th>
                                    <td>Day {{ $raid->round->daysInRound($raid->end_date) }} ({{ $raid->end_date->format('M d, Y H:i') }})</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <tr>
                                    <th width="200">Reward</th>
                                    <td>
                                        @if ($raid->reward_resource && $raid->reward_amount)
                                            {{ number_format($raid->reward_amount) }} {{ $raid->reward_resource }}
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Completion Reward</th>
                                    <td>
                                        @if ($raid->completion_reward_resource && $raid->completion_reward_amount)
                                            {{ number_format($raid->completion_reward_amount) }} {{ $raid->completion_reward_resource }}
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Objectives</th>
                                    <td>{{ $raid->objectives->count() }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h4>Description</h4>
                            <p>{!! $raid->description !!}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Objectives</h3>
                    <div class="pull-right">
                        <a href="{{ route('staff.administrator.raids.objectives.create', $raid) }}" class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Create New Objective
                        </a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    @if ($raid->objectives->isEmpty())
                        <p class="text-center text-muted">No objectives found for this raid.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50" class="text-center">Order</th>
                                    <th>Name</th>
                                    <th width="150" class="text-center">Score Required</th>
                                    <th width="150" class="text-center">Start Date</th>
                                    <th width="150" class="text-center">End Date</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="100" class="text-center">Tactics</th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($raid->objectives->sortBy('order') as $objective)
                                    <tr>
                                        <td class="text-center">{{ $objective->order }}</td>
                                        <td>
                                            <a href="{{ route('staff.administrator.raids.objectives.show', [$raid, $objective]) }}">
                                                <strong>{{ $objective->name }}</strong>
                                            </a>
                                        </td>
                                        <td class="text-center">{{ number_format($objective->score_required) }}</td>
                                        <td class="text-center">
                                            <small>Day {{ $raid->round->daysInRound($objective->start_date) }}</small>
                                        </td>
                                        <td class="text-center">
                                            <small>Day {{ $raid->round->daysInRound($objective->end_date) }}</small>
                                        </td>
                                        <td class="text-center">
                                            {!! $raidHelper->getStatusLabel($objective->status, true) !!}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-blue">{{ $objective->tactics->count() }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('staff.administrator.raids.objectives.show', [$raid, $objective]) }}" class="btn btn-xs btn-primary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('staff.administrator.raids.objectives.edit', [$raid, $objective]) }}" class="btn btn-xs btn-info" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="{{ route('staff.administrator.raids.objectives.delete', [$raid, $objective]) }}" class="btn btn-xs btn-danger" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
