@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($dominions->isEmpty())
                <p>Welcome to OpenDominion.</p>
                <p>To start playing, please register in a round below.</p>
            @else
                <p>Welcome back, {{ Auth::user()->display_name }}.</p>
                <p>Select one of your dominions below to go to its status screen.</p>
            @endif

            @if ($currentRound !== null && !$currentRound->hasEnded())
                The current round is <b>{{ $currentRound->name }}</b>
                @if (!$currentRound->hasStarted())
                    and starts in {{ $currentRound->timeUntilStart() }}
                @endif
                @if (!$currentRound->userAlreadyRegistered(Auth::user()))
                    <a class="btn btn-primary btn-sm" href="{{ route('round.register', $currentRound) }}">Register</a>
                @endif
            @endif
        </div>
    </div>

    <div class="row">

        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="ra ra-capitol ra-fw"></i> Dominions</h3>
                </div>

                @if ($dominions->isEmpty())

                    <div class="card-body">
                        <p>You have no active dominions. Register in a round to create a dominion.</p>
                    </div>

                @else

                    <div class="card-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="15%">
                                <col width="10%">
                                <col width="10%">
                                <col width="10%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th class="text-center">Race</th>
                                    <th class="text-center">Land</th>
                                    <th class="text-center">Networth</th>
                                    <th class="text-center">Round</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dominions->all() as $dominion)
                                    @php $ranks = $rankings->where('dominion_id', $dominion->id)->keyBy('key'); @endphp
                                    <tr>
                                        <td>
                                            @if ($dominion->isSelectedByAuthUser())
                                                <a href="{{ route('dominion.status') }}">{{ $dominion->name }}</a>

                                                @if (!$dominion->round->hasStarted())
                                                    <span class="badge text-bg-warning">Starting soon</span>
                                                @elseif (!$dominion->round->hasEnded())
                                                    <span class="badge text-bg-info">In Progress</span>
                                                @endif

                                                @if ($dominion->locked_at !== null)
                                                    <span class="badge text-bg-danger">Locked</span>
                                                @elseif ($dominion->isAbandoned())
                                                    <span class="badge text-bg-warning">Abandoned</span>
                                                @endif

                                                <span class="badge text-bg-success">Selected</span>
                                            @else
                                                <form action="{{ route('dominion.select', $dominion) }}" method="post">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link" style="padding: 0;">{{ $dominion->name }}</button>

                                                    @if (!$dominion->round->hasStarted())
                                                        <span class="badge text-bg-warning">Starting soon</span>
                                                    @elseif (!$dominion->round->hasEnded())
                                                        <span class="badge text-bg-info">In Progress</span>
                                                    @endif

                                                    @if ($dominion->locked_at !== null)
                                                        <span class="badge text-bg-danger">Locked</span>
                                                    @elseif ($dominion->isAbandoned())
                                                        <span class="badge text-bg-warning">Abandoned</span>
                                                    @endif
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
                                        </td>
                                        <td class="text-center">
                                            {{ isset($ranks['largest-dominions']) ? number_format($ranks['largest-dominions']->value) : null }}
                                        </td>
                                        <td class="text-center">
                                            {{ isset($ranks['strongest-dominions']) ? number_format($ranks['strongest-dominions']->value) : null }}
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->round->number }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif

            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-clock-o fa-fw"></i> Rounds</h3>
                </div>

                @if ($rounds->isEmpty())

                    <div class="card-body">
                        <p>There are currently no active rounds.</p>
                    </div>

                @else

                    <div class="card-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="5%">
                                <col>
                                <col width="20%">
                                <col width="15%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Name</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Register</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rounds->all() as $round)
                                    @php
                                        $trClass = 'danger';
                                        $userAlreadyRegistered = $round->userAlreadyRegistered(Auth::user());

                                        if ($round->hasEnded()) {
                                            $trClass = '';
                                        } elseif ($userAlreadyRegistered) {
                                            $trClass = 'info';
                                        } elseif ($round->hasStarted()) {
                                            $trClass = 'warning';
                                        } else {
                                            $trClass = 'success';
                                        }
                                    @endphp

                                    <tr class="{{ $trClass }}">
                                        <td class="text-center">{{ $round->number }}</td>
                                        <td>
                                            {{ $round->name }}
                                            <span class="text-muted">({{ $round->league->description }})</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($round->hasEnded())
                                                <abbr title="Ended at {{ $round->end_date }}">Ended</abbr>
                                            @elseif ($round->start_date > now())
                                                <abbr title="Starts in {{ $round->timeUntilStart() }}">
                                                    Starts in {{ $round->timeUntilStart() }}
                                                </abbr>
                                            @else
                                                <abbr title="Ending at {{ $round->end_date }}">
                                                    Ending in {{ $round->daysUntilEnd() }} {{ str_plural('day', $round->daysUntilEnd()) }}
                                                </abbr>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($round->hasEnded())
                                                <a href="{{ route('valhalla.round', $round) }}">Valhalla</a>
                                            @elseif ($userAlreadyRegistered && $round->isActive())
                                                Playing
                                            @elseif ($userAlreadyRegistered && !$round->hasStarted())
                                                Registered
                                            @else
                                                <a href="{{ route('round.register', $round) }}" class="btn btn-primary btn-flat btn-sm">Register</a>
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
@endsection
