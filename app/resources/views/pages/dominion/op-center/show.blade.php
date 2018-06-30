@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="row">

                <div class="col-sm-12 col-md-8">
                    @if (!$infoOpService->hasInfoOp($selectedDominion->realm, $dominion, 'clear_sight'))
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">
                                    <i class="fa fa-bar-chart"></i> Status Screen
                                </h3>
                                <div class="pull-right box-tools">
                                    <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                        @csrf
                                        <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                        <input type="hidden" name="spell" value="clear_sight">
                                        <button type="submit" class="btn btn-xs btn-primary">Cast: Clear Sight ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'clear_sight')) }} mana)</button>
                                    </form>
                                </div>
                            </div>
                            <div class="box-body">
                                <p>No recent data available.</p>
                                <p>Cast magic spell 'Clear Sight' to reveal information.</p>
                            </div>
                        </div>
                    @else
                        @php
                            $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'clear_sight');
                            $race = OpenDominion\Models\Race::/*with('units')->*/findOrFail($infoOp->data['race_id']);
                        @endphp
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">
                                    <i class="fa fa-bar-chart"></i> The Dominion of {{ $dominion->name }}
                                </h3>
                                @if ($infoOp->isStale())
                                    <small class="label label-warning">Stale</small>
                                    <div class="pull-right box-tools">
                                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                            @csrf
                                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                            <input type="hidden" name="spell" value="clear_sight">
                                            <button type="submit" class="btn btn-xs btn-success">Recast: Clear Sight ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'clear_sight')) }} mana)</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="pull-right box-tools">
                                        <small>Revealed {{ $infoOp->updated_at->diffForHumans() }} by {{ $infoOp->sourceDominion->name }}</small>
                                    </div>
                                @endif
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
                                                    <th colspan="2">Overview</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Ruler:</td>
                                                    <td>{{ $infoOp->data['ruler_name'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Race:</td>
                                                    <td>{{ $race->name }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Land:</td>
                                                    <td>
                                                        {{ number_format($infoOp->data['land']) }}
                                                        <span class="{{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion) }}">
                                                            ({{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}%)
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Peasants:</td>
                                                    <td>{{ number_format($infoOp->data['peasants']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Employment:</td>
                                                    <td>{{ number_format($infoOp->data['employment'], 2) }}%</td>
                                                </tr>
                                                <tr>
                                                    <td>Networth:</td>
                                                    <td>{{ number_format($infoOp->data['networth']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Prestige:</td>
                                                    <td>{{ number_format($infoOp->data['prestige']) }}</td>
                                                </tr>
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
                                                    <th colspan="2">Resources</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Platinum:</td>
                                                    <td>{{ number_format($infoOp->data['resource_platinum']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Food:</td>
                                                    <td>{{ number_format($infoOp->data['resource_food']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Lumber:</td>
                                                    <td>{{ number_format($infoOp->data['resource_lumber']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Mana:</td>
                                                    <td>{{ number_format($infoOp->data['resource_mana']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Ore:</td>
                                                    <td>{{ number_format($infoOp->data['resource_ore']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Gems:</td>
                                                    <td>{{ number_format($infoOp->data['resource_gems']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="nyi">Research Points:</td>
                                                    <td class="nyi">{{ number_format($infoOp->data['resource_tech']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Boats:</td>
                                                    <td>{{ number_format($infoOp->data['resource_boats']) }}</td>
                                                </tr>
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
                                                    <th colspan="2">Military</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Morale:</td>
                                                    <td>{{ number_format($infoOp->data['morale']) }}%</td>
                                                </tr>
                                                <tr>
                                                    <td>Draftees:</td>
                                                    <td>{{ number_format($infoOp->data['military_draftees']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ $race->units->get(0)->name }}</td>
                                                    <td>{{ number_format($infoOp->data['military_unit1']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ $race->units->get(1)->name }}</td>
                                                    <td>{{ number_format($infoOp->data['military_unit2']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ $race->units->get(2)->name }}</td>
                                                    <td>{{ number_format($infoOp->data['military_unit3']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>{{ $race->units->get(3)->name }}</td>
                                                    <td>{{ number_format($infoOp->data['military_unit4']) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Spies:</td>
                                                    <td>???</td>
                                                </tr>
                                                <tr>
                                                    <td>Wizards:</td>
                                                    <td>???</td>
                                                </tr>
                                                <tr>
                                                    <td>ArchMages:</td>
                                                    <td>???</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="row">
                        <div class="col-sm-12">
                            active spells
                        </div>
                        <div class="col-sm-12">
                            imps
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">

                <div class="col-sm-12 col-md-6">
                    buildings
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="row">
                        <div class="col-sm-12">
                            units
                        </div>
                        <div class="col-sm-12">
                            land
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
                    <p>This page contains the data that your realmies have gathered about dominion <b>{{ $dominion->name }}</b> from realm {{ $dominion->realm->name }} (#{{ $dominion->realm->number }}).</p>

                    <p>Sections marked as <span class="label label-warning">stale</span> contain data from the previous hour (or earlier) and should be considered inaccurate. Recast your info ops before performing any offensive operations during this hour.</p>

                    <p>
                        OP: ???<br>
                        DP: ???<br>
                        Land: {{ $infoOpService->getLandString($selectedDominion->realm, $dominion) }}<br>
                        Networth: {{ $infoOpService->getNetworthString($selectedDominion->realm, $dominion) }}<br>
                    </p>

                    {{-- invade button --}}
                </div>
            </div>
        </div>

    </div>
@endsection