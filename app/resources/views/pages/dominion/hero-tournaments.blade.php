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
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-trophy"></i> {{ $tournament->name }}</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <h4>Standings {{ $tournament->finished ? '- Final' : null }}</h4>
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
                        <h4>Results {{ $tournament->finished ? '- Final' : null }}</h4>
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
        </div>

    </div>
@endsection
