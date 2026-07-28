@extends('layouts.staff')

@section('page-header', 'Rounds')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">Rounds</span>
            <div class="float-end">
                <a href="{{ route('staff.administrator.rounds.create') }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Create New Round
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            @if ($rounds->isEmpty())
                <p class="text-center text-muted">No rounds found.</p>
            @else
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>League</th>
                            <th class="text-center">Start Date</th>
                            <th class="text-center">End Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rounds as $round)
                            <tr>
                                <td class="text-center">{{ $round->number }}</td>
                                <td>
                                    <a href="{{ route('staff.administrator.rounds.show', $round) }}">
                                        <strong>{{ $round->name }}</strong>
                                    </a>
                                </td>
                                <td>{{ $round->description }}</td>
                                <td>{{ $round->league->description ?? $round->league->key }}</td>
                                <td class="text-center">{{ $round->start_date->format('Y-m-d H:i') }}</td>
                                <td class="text-center">{{ $round->end_date->format('Y-m-d H:i') }}</td>
                                <td class="text-center">
                                    @if ($round->hasEnded())
                                        <span class="badge text-bg-secondary">Ended</span>
                                    @elseif ($round->isActive())
                                        <span class="badge text-bg-success">Active (Day {{ $round->daysInRound() }})</span>
                                    @elseif ($round->registrationOpen())
                                        <span class="badge text-bg-info">Registration Open</span>
                                    @else
                                        <span class="badge text-bg-warning">Scheduled</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('staff.administrator.rounds.show', $round) }}" class="btn btn-sm btn-secondary" title="Show"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('staff.administrator.rounds.edit', $round) }}" class="btn btn-sm btn-primary" title="Edit"><i class="fa fa-edit"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
