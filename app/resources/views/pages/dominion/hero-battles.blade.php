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
                                                    @foreach ($heroCalculator->getHeroCombatStats($combatant->hero) as $stat => $value)
                                                        @php
                                                            $heroLevel = $heroCalculator->getHeroLevel($combatant->hero);
                                                            $baseCombatStats = $heroCalculator->getBaseCombatStats($heroLevel);
                                                        @endphp
                                                        <tr>
                                                            <td>
                                                                <span class="{{ $stat == 'focus' && $combatant->has_focus ? 'text-green': null }}">
                                                                    {{ ucwords($stat) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if ($stat == 'health')
                                                                    {{ $combatant->current_health }} /
                                                                @endif
                                                                <span class="{{ $baseCombatStats[$stat] != $value ? 'text-green' : null }}">
                                                                    {{ $value }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
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
                                                                <a class="btn btn-block btn-primary"
                                                                    href="{{ route('dominion.heroes.battles.action', ['combatant'=>$playerCombatant->id, 'action'=>$action]) }}"
                                                                    {{ !$heroHelper->canUseCombatAction($playerCombatant, $action) ? 'disabled' : null }}>
                                                                    {{ ucwords($action) }}
                                                                </a>
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
                                                                <option value="{{ $strategy }}" {{ $action == $playerCombatant->strategy ? 'selected' : null }}>{{ ucwords($strategy) }}</option>
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
                                <div class="col-md-6" style="max-height: {{ $battle->finished ? '250px' : '605px' }}; overflow-y: scroll;">
                                    <table class="table table-condensed">
                                        <thead><tr><th>Combat Log</th></tr></thead>
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
                                            @elseif ($battle->winner_id == null)
                                                Draw
                                            @else
                                                Loss
                                            @endif
                                        </td>
                                        <td>{{ implode(' vs ', $battle->combatants->pluck('name')->toArray()) }}</td>
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
                    Each turn you can choose from one of the five following actions:<br/>
                    <ul>
                        <li>Attack: deals damage equal to your attack minus the opponent's defense (mitigated damage)</li>
                        <li>Defend: prevents damage equal to your defense</li>
                        <li>Focus: increases your attack value by 25% for your next attack action</li>
                        <li>Counter: if attacked, counter attacks for 150% mitigated damage</li>
                        <li>Recover: heals damage equal to your defense</li>
                    </ul>
                    Additionally, each combatant has a chance to evade incoming attack actions, preventing all damage.
                </div>
            </div>
        </div>

    </div>
@endsection
