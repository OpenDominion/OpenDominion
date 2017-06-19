@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="box">
        <div class="box-body">
            @if ($dominions->isEmpty())
                <p>Welcome to OpenDominion.</p>
                <p>To start playing, please register in a round below.</p>
            @else
                <p>Welcome back, {{ Auth::user()->display_name }}.</p>
                <p>Select one of your dominions below to go to its status screen.</p>
            @endif
        </div>
    </div>

    <div class="row">

        <div class="col-lg-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-capitol ra-fw"></i> Dominions</h3>
                </div>

                @if ($dominions->isEmpty())

                    <div class="box-body">
                        <p>You have no active dominions. Register in a round to create a dominion.</p>
                    </div>

                @else

                    <div class="box-body no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="200">
                                <col width="80">
                                <col width="80">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th class="text-center">Realm</th>
                                    <th class="text-center">Race</th>
                                    <th class="text-center">Round</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dominions->all() as $dominion)
                                    <tr>
                                        <td>
                                            @if (!$dominion->round->hasStarted())
                                                {{ $dominion->name }}
                                                <abbr title="Available at {{ $dominion->round->start_date }}" class="label label-primary">In {{ $dominion->round->daysUntilStart() }} day(s)</abbr>

                                            @elseif ($dominion->isSelectedByAuthUser())
                                                <a href="{{ route('dominion.status') }}">{{ $dominion->name }}</a>
                                                <span class="label label-success">Selected</span>

                                                @if ($dominion->isLocked())
                                                    <span class="label label-danger">Locked</span>
                                                @endif
                                            @else
                                                <form action="{{ route('dominion.select', $dominion) }}" method="post">
                                                    {!! csrf_field() !!}
                                                    <button type="submit" class="btn btn-link" style="padding: 0;">{{ $dominion->name }}</button>

                                                    @if ($dominion->isLocked())
                                                        <span class="label label-danger">Locked</span>
                                                    @endif
                                                </form>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            #{{ $dominion->realm->number }}: {{ $dominion->realm->name }}
                                        </td>
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
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
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o fa-fw"></i> Rounds</h3>
                </div>

                @if ($rounds->isEmpty())

                    <div class="box-body">
                        <p>There are currently no active rounds.</p>
                    </div>

                @else

                    <div class="box-body no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="40">
                                <col>
                                <col width="160">
                                <col width="80">
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
                                        } elseif ($round->openForRegistration()) {
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
                                            @elseif ($round->isActive())
                                                <abbr title="Ending at {{ $round->end_date }}">
                                                    Ending in {{ $round->daysUntilEnd() }} day{{ ($round->daysUntilEnd() > 1) ? 's' : '' }}
                                                </abbr>
                                            @else
                                                <abbr title="Starting at {{ $round->start_date }}">
                                                    Starting in {{ $round->daysUntilStart() }} day{{ ($round->daysUntilStart() > 1) ? 's' : '' }}
                                                </abbr>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($round->hasEnded())
                                                &nbsp;
                                            @elseif ($userAlreadyRegistered && $round->isActive())
                                                Playing
                                            @elseif ($userAlreadyRegistered && !$round->hasStarted())
                                                Registered
                                            @elseif ($round->openForRegistration())
                                                <a href="{{ route('round.register', $round) }}" class="btn btn-primary btn-flat btn-xs">Register</a>
                                            @else
                                                In {{ $round->daysUntilRegistration() }} day(s)
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
