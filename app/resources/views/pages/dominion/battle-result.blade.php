@extends('layouts.master')

@section('page-header', 'Battle Result')

@section('content')
    @if ($battleResult->result->success)
        <div class="alert alert-success">
            <p>You successfully invaded {{ $defenderDominion->name }} (#{{ $defenderDominion->realm->number }}), conquering {{ number_format(collect($battleResult->attacker->landConquered)->sum()) }} acres of land.</p>

            @foreach ($battleResult->attacker->converts ?? [] as $unitSlot => $amount)
                <p>In addition, your army converts some of the enemy casualties into 0 Skeletons, 0 Ghouls and 995 Progeny!</p>
            @endforeach
        </div>
    @else
        <div class="alert alert-danger">
            <p>You failed to invade {{ $defenderDominion->name }} (#{{ $defenderDominion->realm->number }}).</p>
        </div>
    @endif

    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-crossed-swords"></i>
                        {{ $attackerDominion->name }} (#{{ $attackerDominion->realm->number }})
                        vs
                        {{ $defenderDominion->name }} (#{{ $defenderDominion->realm->number }})
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Your Losses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($battleResult->attacker->unitsLost as $unitSlot => $amount)
                                        <tr>
                                            <td>
                                                {{ $attackerDominion->race->units()->where('slot', $unitSlot)->first()->name }}
                                            </td>
                                            <td>{{ number_format($amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Their Losses</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($battleResult->defender->unitsLost as $unitSlot => $amount)
                                        <tr>
                                            <td>
                                                @if ($unitSlot === 'draftees')
                                                    Draftees
                                                @else
                                                    {{ $defenderDominion->race->units()->where('slot', $unitSlot)->first()->name }}
                                                @endif
                                            </td>
                                            <td>{{ number_format($amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Land Conquered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($battleResult->attacker->landConquered as $landType => $amount)
                                        <tr>
                                            <td>{{ ucfirst($landType) }}</td>
                                            <td>{{ number_format($amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($battleResult->result->success)
        <p class="text-center text-green">
            You gained {{ number_format($battleResult->attacker->prestigeChange) }} prestige.
        </p>
    @else
        @if (isset($battleResult->result->outmatchedCasualtiesMultiplier))
            <p class="text-center text-red">
                Because you were severely outmatched, you suffered {{ number_format($battleResult->result->outmatchedCasualtiesMultiplier * 100) }}% extra casualties.
            </p>
        @endif

        <p class="text-center text-red">
            You lost {{ number_format($battleResult->attacker->prestigeChange) }} prestige.
        </p>
    @endif

    @if (isset($battleresult->defender->recentlyInvadedLevel))
        {{-- @switch ($battleresult->defender->recentlyInvadedLevel) --}}
        {{-- todo :) --}}
    @endif
@endsection
