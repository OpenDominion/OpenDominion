@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-axe"></i> Battle Report</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                @foreach ($battle->combatants as $combatant)
                                    <div class="col-sm-6">
                                        <table class="table table-condensed">
                                            <thead>
                                                <tr>
                                                    <th colspan=2 class="text-center">
                                                        {{ $combatant->name }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            @foreach ($heroCalculator->getBaseCombatStats() as $stat => $value)
                                                <tr>
                                                    <td>
                                                        {{ ucwords($stat) }}
                                                    </td>
                                                    <td>
                                                        @if ($stat == 'health')
                                                            {{ $combatant->current_health }} /
                                                        @endif
                                                        {{ $combatant->{$stat} }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td><span data-toggle="tooltip" title="Time remaining to set manual actions">Time</span></td>
                                                <td>{{ rfloor($combatant->timeLeft() / 3600) }}h, {{ rfloor($combatant->timeLeft() % 3600 / 60) }}m</td>
                                            </tr>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="text-center">
                                        @if ($battle->finished)
                                            @if ($battle->winner == null)
                                                <h4>Draw!</h4>
                                            @else
                                                <h4>{{ $battle->winner->name }} wins!</h4>
                                            @endif
                                        @else
                                            <h4>Combat in progress...</h4>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Combat Log</th>
                                    </tr>
                                </thead>
                                @foreach ($battle->actions->sortByDesc('turn')->groupBy('turn') as $turn => $actions)
                                    <tr><td>Turn {{ $turn }}</td></tr>
                                    <tr><td>
                                        @foreach ($actions as $action)
                                            {{ $action->combatant->name }} selected {{ ucwords($action->action) }}.<br/>
                                        @endforeach
                                        @foreach ($actions->where('description', '!=', '') as $action)
                                            {{ $action->description }}<br/>
                                        @endforeach
                                    </td></tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    @include('partials.dominion.hero-combat')
                </div>
            </div>
        </div>

    </div>
@endsection
