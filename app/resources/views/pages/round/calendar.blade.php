@extends('layouts.topnav')

@section('title', 'Round Calendar')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-calendar-days me-2"></i> Round Calendar</span>
                </div>
                <div class="card-body">
                    <p class="text-muted">Active and upcoming rounds. Registration opens {{ \OpenDominion\Models\Round::REGISTRATION_OPEN_DAYS_BEFORE_START }} days before start.</p>

                    @if ($activeRounds->isEmpty() && $upcomingRounds->isEmpty())
                        <p class="text-center text-muted">There are no active or upcoming rounds scheduled.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Round</th>
                                        <th>Description</th>
                                        <th class="text-center">League</th>
                                        <th class="text-center">Registration Opens</th>
                                        <th class="text-center">Starts</th>
                                        <th class="text-center">Ends</th>
                                        <th class="text-center">Duration</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeRounds->concat($upcomingRounds) as $round)
                                        <tr @class(['table-success' => $round->isActive()])>
                                            <td>
                                                <strong>{{ $round->name }}</strong>
                                            </td>
                                            <td>{{ $round->description }}</td>
                                            <td class="text-center">{{ $round->league->description ?? $round->league->key }}</td>
                                            <td class="text-center">
                                                <abbr title="{{ $round->registrationOpensAt() }}">{{ $round->registrationOpensAt()->format('M j, Y') }}</abbr>
                                                <br>
                                                <small class="text-muted">
                                                    @if ($round->registrationOpen())
                                                        open now
                                                    @else
                                                        in {{ now()->longAbsoluteDiffForHumans($round->registrationOpensAt(), 2) }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <abbr title="{{ $round->start_date }}">{{ $round->start_date->format('M j, Y') }}</abbr>
                                                <br>
                                                <small class="text-muted">
                                                    @if ($round->hasStarted())
                                                        {{ now()->longAbsoluteDiffForHumans($round->start_date, 2) }} ago
                                                    @else
                                                        in {{ $round->timeUntilStart() }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <abbr title="{{ $round->end_date }}">{{ $round->end_date->format('M j, Y') }}</abbr>
                                                <br>
                                                <small class="text-muted">in {{ now()->longAbsoluteDiffForHumans($round->end_date, 2) }}</small>
                                            </td>
                                            <td class="text-center">{{ $round->durationInDays() }} days</td>
                                            <td class="text-center">
                                                @if ($round->isActive())
                                                    <span class="badge text-bg-success">Active — Day {{ $round->daysInRound() }}</span>
                                                    @if (Auth::check())
                                                        <br>
                                                        @if ($round->userAlreadyRegistered(Auth::user()))
                                                            <small class="text-muted">Playing</small>
                                                        @else
                                                            <a href="{{ route('round.register', $round) }}" class="btn btn-primary btn-sm mt-1">Register</a>
                                                        @endif
                                                    @endif
                                                @elseif ($round->registrationOpen())
                                                    @if (Auth::check() && $round->userAlreadyRegistered(Auth::user()))
                                                        <span class="badge text-bg-secondary">Registered</span>
                                                    @else
                                                        <a href="{{ route('round.register', $round) }}" class="btn btn-primary btn-sm">Register</a>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Not yet open</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
