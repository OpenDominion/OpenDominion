@extends('layouts.master')

@section('page-header', 'Hero Tournament')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if ($tournaments->isEmpty())
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-trophy"></i> Hero Tournament</h3>
                    </div>
                    <div class="box-body table-responsive">
                        There is no tournament at the moment.
                    </div>
                </div>
            @endif
            @foreach ($tournaments as $tournament)
                @if ($tournament->start_date && !$tournament->hasStarted())
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-trophy"></i> {{ $tournament->name }}</h3>
                        </div>
                        <div class="box-body">
                            <div class="alert alert-info">
                                <p>The tournament grounds buzz with anticipation as warriors from across the land prepare for the ultimate test of skill and valor. Preparations are underway, banners are being raised, and crowds are gathering to witness the legendary battles to come.</p>
                                <p><strong>The tournament begins in {{ $tournament->start_date->longAbsoluteDiffForHumans() }}.</strong></p>
                            </div>
                            @if ($tournament->participants->pluck('hero_id')->contains($selectedDominion->hero->id))
                                <div class="alert alert-success">
                                    <h4>You Are Registered!</h4>
                                    <p>Your hero stands ready among the champions, their name already inscribed in the tournament scrolls. The time for glory draws near.</p>
                                </div>
                                <p>
                                    <a href="{{ route('dominion.heroes.tournaments.leave', ['tournament' => $tournament->id]) }}" class="btn btn-danger">
                                        Leave Tournament
                                    </a>
                                </p>
                            @else
                                <div class="alert alert-warning">
                                    <h4>Your Hero Awaits!</h4>
                                    <p>The call to arms has been sounded, but your hero has not yet answered. Will they step forward to claim their destiny?</p>
                                </div>
                                <p>
                                    <a href="{{ route('dominion.heroes.tournaments.join', ['tournament' => $tournament->id]) }}" class="btn btn-primary">
                                        Join Tournament
                                    </a>
                                </p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-trophy"></i> {{ $tournament->name }}</h3>
                        </div>
                        <div class="box-body">
                            @if (!$tournament->finished && !$tournament->battles->where('finished', true)->isEmpty())
                                @php $battle = $tournament->battles->where('finished', true)->sortByDesc('updated_at')->first(); @endphp
                                <div class="panel panel-info">
                                    <div class="panel-body">
                                        <div class="form-group">
                                            <i class="fa fa-clock-o"></i> Most Recent Battle - {{ implode(' vs ', $battle->combatants->pluck('name')->toArray()) }}
                                            <div class="pull-right text-muted">{{ $battle->updated_at->diffForHumans() }}</div>
                                        </div>
                                        {{ $heroHelper->getBattleResult($battle) }}
                                    </div>
                                </div>
                            @endif
                            <h4>Standings {{ $tournament->finished ? '- Final' : null }}</h4>
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col width="100">
                                        <col>
                                        <col>
                                        <col width="100">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Standing</th>
                                            <th>Dominion</th>
                                            <th>Hero</th>
                                            <th>Record</th>
                                        </tr>
                                    </thead>
                                    @foreach ($tournament->participants->sortBy([['standing', 'asc'], ['wins', 'desc'], ['losses', 'asc'], ['draws', 'desc']]) as $participant)
                                        <tr>
                                            <td>
                                                {{ $participant->standing ? $participant->standing : '--' }}
                                            </td>
                                            <td>
                                                {{ $participant->hero->dominion->name }} (#{{ $participant->hero->dominion->realm->number }})
                                            </td>
                                            <td>
                                                {{ $participant->hero->name }}
                                            </td>
                                            <td class="{{ $participant->eliminated ? 'text-red' : null }}">
                                                {{ $participant->wins }} - {{ $participant->losses }} - {{ $participant->draws }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                            <h4>Results {{ $tournament->finished ? '- Final' : null }}</h4>
                            <div class="table-responsive">
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col width="100">
                                        <col>
                                        <col>
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Round</th>
                                            <th>Matchup</th>
                                            <th>Winner</th>
                                        </tr>
                                    </thead>
                                    @foreach ($tournament->battles->sortBy([['pivot.round_number', 'desc'], ['finished', 'desc'], ['winner', 'desc']]) as $battle)
                                        <tr>
                                            <td>{{ $battle->pivot->round_number }}</td>
                                            <td>{{ implode(' vs ', $battle->combatants->pluck('name')->toArray()) }}</td>
                                            <td>
                                                @if ($battle->winner)
                                                    {{ $battle->winner->name }}
                                                @elseif ($battle->finished)
                                                    <span class="text-muted">Draw</span>
                                                @else
                                                    --
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
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

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Class-Based Abilities</h3>
                </div>
                <div class="box-body">
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
        </div>

    </div>
@endsection
