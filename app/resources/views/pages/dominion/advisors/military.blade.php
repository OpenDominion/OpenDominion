@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Military Advisor';
    if ($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }

    $militaryData = $infoMapper->mapMilitary($target, false);
    $resourceData = $infoMapper->mapResources($target);
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">
        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> {{ $pageHeader }}</h3>
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
                                        <th colspan="2">Offense</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Offensive Power:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getOffensivePower($target)) }}</strong>
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($target) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($target))) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Offensive Power Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($militaryCalculator->getOffensivePowerMultiplier($target) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Offense Ratio:</td>
                                        <td>
                                            <strong>{{ number_format(($militaryCalculator->getOffensivePower($target) / $landCalculator->getTotalLand($target)), 3) }}</strong>
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($target) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($target) / $landCalculator->getTotalLand($target)), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Offensive Casualties Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($casualtiesCalculator->getOffensiveCasualtiesMultiplier($target) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boat Capacity:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getBoatCapacity($target)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Temple Reduction:</td>
                                        <td>
                                            <strong>{{ number_string($militaryCalculator->getTempleReduction($target) * 100, 3) }}%</strong>
                                        </td>
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
                                        <th colspan="2">Defense</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Defensive Power:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getDefensivePower($target)) }}</strong>
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($target) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($target))) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Power Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($militaryCalculator->getDefensivePowerMultiplier($target) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defense Ratio:</td>
                                        <td>
                                            <strong>{{ number_format(($militaryCalculator->getDefensivePower($target) / $landCalculator->getTotalLand($target)), 3) }}</strong>
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($target) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($target) / $landCalculator->getTotalLand($target)), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Casualties Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($casualtiesCalculator->getDefensiveCasualtiesMultiplier($target) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boats Protected:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getBoatsProtected($target)) }}</strong>
                                        </td>
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
                                        <th colspan="2">Invasions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Attacking success:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_attacking_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Attacking failure:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_attacking_failure) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defending success:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_defending_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defending failure:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_defending_failure) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                                <thead>
                                    <tr>
                                        <th colspan="2">Offensive Casualties</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $unitHelper->getUnitName('unit1', $target->race) }}:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_military_unit1_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ $unitHelper->getUnitName('unit3', $target->race) }}:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_military_unit3_lost) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>{{ $unitHelper->getUnitName('unit4', $target->race) }}:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_military_unit4_lost) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-md-3">
            @include('partials.dominion.military.mods')
        </div>
    </div>

    <div class="row">

        <div class="col-md-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Units in training and home</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.military-training-table', ['data' => $militaryData, 'isOp' => false, 'race' => $target->race ])
                </div>
            </div>
        </div>

        <div class="col-md-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Units returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.military-returning-table', ['data' => $militaryData, 'isOp' => false, 'race' => $target->race ])
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Resources returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.resources-incoming-table', ['data' => $resourceData])
                </div>
            </div>
        </div>

    </div>
@endsection
