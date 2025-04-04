@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-axe"></i> Active Battles</h3>
                </div>
                <div class="box-body">
                    @php $playerCombatant = null; @endphp
                    @foreach ($activeBattles as $battle)
                        <form class="form-horizontal" action="{{ route('dominion.heroes.battles') }}" method="post" role="form">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row">
                                        @foreach ($battle->combatants as $combatant)
                                            @if ($combatant->hero_id == $hero->id)
                                                @php $playerCombatant = $combatant; @endphp
                                                <input type="hidden" name="combatant" value="{{ $combatant->id }}">
                                            @endif
                                            <div class="col-sm-6">
                                                <table class="table table-condensed">
                                                    <thead>
                                                        <tr>
                                                            <th colspan=2 class="text-center">
                                                                {{ $combatant->name }}
                                                                @if ($combatant->hero_id == $hero->id)
                                                                    (you)
                                                                @endif
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    @foreach ($heroCalculator->getBaseCombatStats($combatant->level) as $stat => $value)
                                                        <tr>
                                                            <td>
                                                                <span class="{{ $stat == 'focus' && $combatant->has_focus ? 'text-green': null }}" data-toggle="tooltip" title="{{ $heroHelper->getCombatStatTooltip($stat) }}">
                                                                    {{ ucwords($stat) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if ($stat == 'health')
                                                                    {{ $combatant->current_health }} /
                                                                @endif
                                                                <span class="{{ $combatant->{$stat} != $value ? 'text-green' : null }}">
                                                                    {{ $combatant->{$stat} }}
                                                                </span>
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
                                            @if ($battle->finished)
                                                <div class="text-center">
                                                    @if ($battle->winner == null)
                                                        <h4>Draw!</h4>
                                                    @else
                                                        <h4>{{ $battle->winner->name }} wins!</h4>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="form-group">
                                                    <div class="col-sm-6">
                                                        <label class="form-label">
                                                            Actions in queue
                                                        </label>
                                                        <table class="table-condensed">
                                                            @foreach ($playerCombatant->actions ?? [] as $idx => $action)
                                                                <tr>
                                                                    <td>{{ $battle->current_turn + $idx }}</td>
                                                                    <td>{{ ucwords($action) }}</td>
                                                                    <td>
                                                                        <a href="{{ route('dominion.heroes.battles.action.delete', ['combatant'=>$playerCombatant->id, 'action'=>$idx]) }}">
                                                                            <i class="fa fa-trash text-danger"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </table>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label">
                                                            Perform/Queue an action
                                                        </label>
                                                        <div>
                                                            @foreach ($heroHelper->getCombatActions() as $action)
                                                                @if (!$heroHelper->canUseCombatAction($playerCombatant, $action) || $playerCombatant->time_bank <= 0)
                                                                    <a class="btn btn-block btn-default" disabled>
                                                                        {{ ucwords($action) }}
                                                                    </a>
                                                                @else
                                                                    <a class="btn btn-block btn-primary" href="{{ route('dominion.heroes.battles.action', ['combatant'=>$playerCombatant->id, 'action'=>$action]) }}">
                                                                        {{ ucwords($action) }}
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="col-sm-12">
                                                        <label class="form-label">
                                                            Strategy <small>(for turns taken while offline)</small>
                                                        </label>
                                                        <select name="strategy" class="form-control">
                                                            @foreach ($heroHelper->getCombatStrategies() as $strategy)
                                                                <option value="{{ $strategy }}" {{ $strategy == $playerCombatant->strategy ? 'selected' : null }}>{{ ucwords($strategy) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="col-sm-9">
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="automated" {{ $playerCombatant->automated != false ? 'checked' : null }}>
                                                                Automate all of my turns
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <button class="btn btn-primary">Update</button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6" style="max-height: {{ $battle->finished ? '272px' : '645px' }}; overflow-y: scroll;">
                                    <table class="table table-condensed">
                                        <thead>
                                            <tr>
                                                <th>Combat Log</th>
                                            </tr>
                                        </thead>
                                        @foreach ($battle->actions->groupBy('turn')->sortDesc() as $turn => $actions)
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
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-axe"></i> Previous Battles</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Result</th>
                                        <th>Combatants</th>
                                        <th>Winner</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                @foreach ($inactiveBattles as $battle)
                                    <tr>
                                        <td>
                                            @if ($battle->winner !== null && $battle->winner->hero_id == $hero->id)
                                                Win
                                            @elseif ($battle->winner == null)
                                                Draw
                                            @else
                                                Loss
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('dominion.heroes.battles.report', ['battle'=>$battle->id]) }}">
                                                {{ implode(' vs ', $battle->combatants->pluck('name')->toArray()) }}
                                            </a>
                                        </td>
                                        <td>{{ $battle->winner ? $battle->winner->name : '--' }}</td>
                                        <td>{{ $battle->created_at }}</td>
                                    </tr>
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
                    @if ($activeBattles->where('finished', false)->count() == 0)
                        <a class="btn btn-primary btn-block" href="{{ route('dominion.heroes.battles.practice') }}">
                            Start Practice Battle
                        </a>
                        @if ($hero->isInQueue())
                            <a class="btn btn-danger btn-block" href="{{ route('dominion.heroes.battles.dequeue') }}">
                                Leave Queue
                            </a>
                        @else
                            <a class="btn btn-primary btn-block" href="{{ route('dominion.heroes.battles.queue') }}">
                                Queue for Battle
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection
