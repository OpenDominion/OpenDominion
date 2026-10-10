@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-axe"></i> Active Battles</span>
                </div>
                <div class="card-body">
                    @foreach ($activeBattles as $battle)
                        @php $view = $battlePresenter->present($battle, $hero->id); @endphp
                        <form action="{{ route('dominion.heroes.battles') }}" method="post" role="form">
                            @csrf
                            @if ($view->viewer)
                                <input type="hidden" name="combatant" value="{{ $view->viewer->id }}">
                            @endif
                            <div class="row">
                                <div class="col-md-6">
                                    @php
                                        $sides = $view->sides();
                                        $active = !$battle->finished && $view->viewer;
                                        $showQueue = $active && $view->canQueueAhead();
                                        $queueBesideOpponent = $showQueue && count($sides) === 2 && count($sides[1]['combatants']) === 1;
                                    @endphp
                                    <div class="row">
                                        @foreach ($sides as $side)
                                            <div class="col-sm">
                                                <h5 class="mb-2 text-center">{{ $side['label'] }}</h5>
                                                <div class="row">
                                                    @foreach ($side['combatants'] as $combatant)
                                                        @include('partials.dominion.hero-combatant', ['view' => $view, 'combatant' => $combatant])
                                                    @endforeach
                                                </div>
                                                @if ($active && $side['team'] === $view->viewer->team)
                                                    <div class="mb-3">
                                                        <label class="form-label">
                                                            {{ $showQueue ? 'Perform/Queue an action' : 'Perform an action' }}
                                                        </label>
                                                        <div>
                                                            @foreach ($view->abilities() as $ability)
                                                                @php
                                                                    $label = $ability['name'];
                                                                    if ($ability['cooldown'] > 0) {
                                                                        $label .= " ({$ability['cooldown']})";
                                                                    }
                                                                    if ($ability['charges'] !== null) {
                                                                        $label .= " [{$ability['charges']}]";
                                                                    }
                                                                    $targets = $ability['needsTarget'] ? $view->targetsFor($ability['key']) : [];
                                                                @endphp
                                                                @if (!$ability['usable'] || ($ability['needsTarget'] && count($targets) == 0))
                                                                    <a class="btn btn-block btn-secondary disabled mb-1" aria-disabled="true" tabindex="-1" title="{{ $ability['description'] }}">
                                                                        {{ $label }}
                                                                    </a>
                                                                @elseif ($ability['needsTarget'] && count($targets) > 1)
                                                                    <div class="dropdown mb-1">
                                                                        <button class="btn btn-block btn-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $ability['description'] }}">
                                                                            {{ $label }}
                                                                        </button>
                                                                        <ul class="dropdown-menu w-100">
                                                                            @foreach ($targets as $target)
                                                                                <li>
                                                                                    <a class="dropdown-item" href="{{ route('dominion.heroes.battles.action', ['combatant'=>$view->viewer->id, 'target'=>$target->id, 'action'=>$ability['key']]) }}">
                                                                                        {{ $target->name }} <small class="text-muted">({{ $target->currentHealth }} hp)</small>
                                                                                    </a>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                @else
                                                                    <a class="btn btn-block btn-primary mb-1" title="{{ $ability['description'] }}"
                                                                        href="{{ route('dominion.heroes.battles.action', ['combatant'=>$view->viewer->id, 'target'=>$targets[0]->id ?? null, 'action'=>$ability['key']]) }}">
                                                                        {{ $label }}
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @if ($showQueue && !$queueBesideOpponent)
                                                        @include('partials.dominion.hero-battle-queue', ['view' => $view])
                                                    @endif
                                                @elseif ($queueBesideOpponent)
                                                    @include('partials.dominion.hero-battle-queue', ['view' => $view])
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            @if ($battle->finished)
                                                <div class="text-center">
                                                    @if ($battle->isDraw())
                                                        <h4>Draw!</h4>
                                                    @else
                                                        <h4>{{ $battle->winnerLabel() }} {{ $battle->winningCombatants()->whereNotNull('hero_id')->count() > 1 ? 'win' : 'wins' }}!</h4>
                                                    @endif
                                                </div>
                                            @elseif ($view->viewer)
                                                <div class="row mb-3">
                                                    <div class="col-sm-12">
                                                        <label class="form-label">
                                                            Strategy <small>(for turns taken while offline)</small>
                                                        </label>
                                                        <select name="strategy" class="form-select">
                                                            @foreach ($view->strategies() as $key => $strategyName)
                                                                <option value="{{ $key }}" {{ $view->viewer->ai == $key ? 'selected' : null }}>{{ $strategyName }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-sm-9">
                                                        <div class="form-check">
                                                            <input type="checkbox" id="automated" name="automated" class="form-check-input" {{ $view->viewer->automated ? 'checked' : null }}>
                                                            <label for="automated" class="form-check-label">
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
                                <div class="col-md-6" style="max-height: {{ count($view->battle->combatants()) > 2 ? '645px' : '272px' }}; overflow-y: scroll;">
                                    @include('partials.dominion.hero-combat-log', ['view' => $view, 'battle' => $battle])
                                </div>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-axe"></i> Previous Battles</span>
                    <div class="float-end">
                        <a href="{{ route('dominion.heroes.battles.leaderboard') }}">View Leaderboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <table class="table table-sm">
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
                                            @if ($battle->isDraw())
                                                Draw
                                            @elseif ($battle->isWinner($battle->combatants->firstWhere('hero_id', $hero->id)))
                                                Win
                                            @else
                                                Loss
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('dominion.heroes.battles.report', ['battle'=>$battle->id]) }}">
                                                {{ $battle->matchupLabel() }}
                                            </a>
                                        </td>
                                        <td>{{ $battle->winnerLabel() ?? '--' }}</td>
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
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    @include('partials.dominion.hero-combat')
                    @if ($activeBattles->where('finished', false)->count() == 0)
                        <a class="btn btn-primary btn-block mb-2" href="{{ route('dominion.heroes.battles.practice') }}">
                            Practice Battles
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

            <!--
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Class-Based Abilities</span>
                </div>
                <div class="card-body">
                    Each hero gains one ability based on their currently active class:
                    <ul>
                        <li>Alchemist - Volatile Mixture: Attack for 150% damage, but 20% chance to hit yourself.</li>
                        <li>Architect - Fortify: Prevent the next 20 non-counter damage dealt.</li>
                        <li>Blacksmith - Forge: Increases attack value by 1 for the remainder of the battle.</li>
                        <li>Engineer - Tactical Awareness: Reduces target's counter value by 2 for the remainder of the battle.</li>
                        <li>Farmer - Hardiness: Remain on 1 health the first time your health would be reduced below 1.</li>
                        <li>Healer - Mending: Focus enhances your Recover ability, increasing healing.</li>
                        <li>Infiltrator - Shadow Strike: Attack that cannot be evaded and deals +2 damage if the target is defending.</li>
                        <li>Sorcerer - Channeling: Focus can be used while already active, stacking bonus damage.</li>
                        <li>Scholar - Combat Analysis: Decreases target's defense value by 1 for the remainder of the battle.</li>
                        <li>Scion - Last Stand: When at 40 health or less, all combat stats are increased by 10%.</li>
                    </ul>
                </div>
            </div>
            -->
        </div>

    </div>
@endsection
