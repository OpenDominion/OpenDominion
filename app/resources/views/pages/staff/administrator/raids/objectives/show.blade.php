@extends('layouts.staff')

@section('page-header', 'Objective Details')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $objective->name }}</h3>
                    <div class="pull-right">
                        <a href="{{ route('staff.administrator.raids.objectives.edit', [$raid, $objective]) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('staff.administrator.raids.objectives.delete', [$raid, $objective]) }}" class="btn btn-danger btn-sm">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                        <a href="{{ route('staff.administrator.raids.show', $raid) }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to Raid
                        </a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <tr>
                                    <th width="200">Raid</th>
                                    <td><a href="{{ route('staff.administrator.raids.show', $raid) }}">{{ $raid->name }}</a></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>{!! $raidHelper->getStatusLabel($objective->status, true) !!}</td>
                                </tr>
                                <tr>
                                    <th>Display Order</th>
                                    <td>{{ $objective->order }}</td>
                                </tr>
                                <tr>
                                    <th>Score Required</th>
                                    <td>{{ number_format($objective->score_required) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <tr>
                                    <th width="200">Start Date</th>
                                    <td>Day {{ $raid->round->daysInRound($objective->start_date) }} ({{ $objective->start_date->format('M d, Y H:i') }})</td>
                                </tr>
                                <tr>
                                    <th>End Date</th>
                                    <td>Day {{ $raid->round->daysInRound($objective->end_date) }} ({{ $objective->end_date->format('M d, Y H:i') }})</td>
                                </tr>
                                <tr>
                                    <th>Tactics</th>
                                    <td>{{ $objective->tactics->count() }}</td>
                                </tr>
                                <tr>
                                    <th>Total Contributions</th>
                                    <td>{{ number_format($objective->contributions->sum('score')) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h4>Description</h4>
                            <p>{!! $objective->description !!}</p>
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
                    <h3 class="box-title">Tactics</h3>
                    <div class="pull-right">
                        <a href="{{ route('staff.administrator.raids.objectives.tactics.create', [$raid, $objective]) }}" class="btn btn-success btn-sm">
                            <i class="fa fa-plus"></i> Create New Tactic
                        </a>
                    </div>
                </div>
                <div class="box-body table-responsive">
                    @if ($objective->tactics->isEmpty())
                        <p class="text-center text-muted">No tactics found for this objective.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50" class="text-center">ID</th>
                                    <th width="120">Type</th>
                                    <th>Name</th>
                                    <th width="200">Attributes</th>
                                    <th width="200">Bonuses</th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($objective->tactics->sortBy('sort_order') as $tactic)
                                    <tr>
                                        <td class="text-center">{{ $tactic->id }}</td>
                                        <td>
                                            <span class="label label-primary">{{ ucfirst($tactic->type) }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $tactic->name }}</strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if (!empty($tactic->attributes))
                                                    {{ count($tactic->attributes) }} attribute(s)
                                                @else
                                                    None
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if (!empty($tactic->bonuses))
                                                    {{ count($tactic->bonuses) }} bonus(es)
                                                @else
                                                    None
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('staff.administrator.raids.objectives.tactics.edit', [$raid, $objective, $tactic]) }}" class="btn btn-xs btn-info" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="{{ route('staff.administrator.raids.objectives.tactics.delete', [$raid, $objective, $tactic]) }}" class="btn btn-xs btn-danger" title="Delete">
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
