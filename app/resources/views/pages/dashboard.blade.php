@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-6">

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-capitol ra-fw"></i> Dominions</h3>
                </div>
                <div class="box-body">

                    @if ($dominions->isEmpty())
                        <p>You have no active dominions. Register in a round to create a dominion.</p>
                    @else
                        @foreach($dominions->chunk(3) as $dominion)
                            <div class="row">
                                <div class="col-sm-4">
                                    dom todo
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

        </div>
        <div class="col-lg-6">

            <div class="box box-default">
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
                                <col width="80">
                                <col width="00">
                                <col width="80">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Name</th>
                                    <th class="text-center">Start</th>
                                    <th class="text-center">Duration</th>
                                    <th class="text-center">Register</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rounds->all() as $round)
                                    @php
                                        $trClass = 'danger';
                                        $userAlreadyRegistered = $round->userAlreadyRegistered(Auth::user());

                                        if ($userAlreadyRegistered) {
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
                                            @if ($round->hasStarted())
                                                <abbr class="text-warning" title="Started at {{ $round->start_date }}">Started!</abbr>
                                                {{-- todo: Show current round milestone (mid, end etc) with appropriate text color --}}
                                            @else
                                                <abbr title="Starting at {{ $round->start_date }}">In {{ $round->daysUntilStart() }} day(s)</abbr>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <abbr title="Ending at {{ $round->end_date }}">{{ $round->durationInDays() }} days</abbr>
                                        </td>
                                        <td class="text-center">
                                            @if ($userAlreadyRegistered)
                                                Already registered!
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


    <p>space</p>
    <p>space</p>
    <p>space</p>





    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="ra ra-capitol ra-fw"></i> Dominions
                </div>
                <div class="panel-body">
                    @if ($dominions->isEmpty())
                        <p>You have no active dominions. Register in a round below to create a dominion.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <colgroup>
                                    <col>
                                    <col width="120">
                                    <col width="120">
                                    <col width="120">
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
{{--                                                <a href="{{ route('dominion.status', $dominion) }}">{{ $dominion->name }}</a>--}}
                                                <a href="#">{{ $dominion->name }}</a>
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
                        <p>Click on a dominion's name to go to its status page.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
