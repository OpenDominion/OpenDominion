@extends('layouts.staff')

@section('page-header', 'Hero Tournament Details')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">{{ $tournament->name }}</span>
                    <div class="float-end">
                        <a href="{{ route('staff.administrator.hero-tournaments.edit', $tournament) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('staff.administrator.hero-tournaments.delete', $tournament) }}" class="btn btn-danger btn-sm">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                        <a href="{{ route('staff.administrator.hero-tournaments.index', ['round' => $tournament->round_id]) }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="200">Round</th>
                                    <td>{{ $tournament->round->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if ($tournament->finished)
                                            <span class="badge text-bg-secondary">Finished</span>
                                        @elseif ($tournament->hasStarted())
                                            <span class="badge text-bg-success">In Progress</span>
                                        @elseif ($tournament->start_date)
                                            <span class="badge text-bg-info">Registration Open</span>
                                        @else
                                            <span class="badge text-bg-warning">Draft</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Start Date</th>
                                    <td>
                                        @if ($tournament->start_date)
                                            Day {{ $tournament->round->daysInRound($tournament->start_date) }} ({{ $tournament->start_date->format('M d, Y H:i') }})
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="200">Current Round</th>
                                    <td>{{ $tournament->current_round_number }}</td>
                                </tr>
                                <tr>
                                    <th>Participants</th>
                                    <td>{{ $tournament->participants->count() }}</td>
                                </tr>
                                <tr>
                                    <th>Winner</th>
                                    <td>
                                        @if ($tournament->winner)
                                            {{ $tournament->winner->name }}
                                        @else
                                            <span class="text-muted">TBD</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Participants</span>
                </div>
                <div class="card-body table-responsive">
                    @if ($tournament->participants->isEmpty())
                        <p class="text-center text-muted">No participants registered yet.</p>
                    @else
                        <table class="table table-hover">
                            <colgroup>
                                <col width="50">
                                <col>
                                <col width="100">
                                <col width="80">
                                <col width="80">
                                <col width="80">
                                <col width="100">
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Hero</th>
                                    <th class="text-center">Wins</th>
                                    <th class="text-center">Losses</th>
                                    <th class="text-center">Draws</th>
                                    <th class="text-center">Standing</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tournament->participants->sortBy('standing') as $participant)
                                    <tr class="{{ $participant->eliminated ? 'text-muted' : null }}">
                                        <td class="text-center">{{ $participant->standing ?? '-' }}</td>
                                        <td>
                                            @if ($participant->hero)
                                                {{ $participant->hero->name }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $participant->wins }}</td>
                                        <td class="text-center">{{ $participant->losses }}</td>
                                        <td class="text-center">{{ $participant->draws }}</td>
                                        <td class="text-center">{{ $participant->standing ?? '-' }}</td>
                                        <td class="text-center">
                                            @if ($participant->eliminated)
                                                <span class="badge text-bg-danger">Eliminated</span>
                                            @else
                                                <span class="badge text-bg-success">Active</span>
                                            @endif
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
