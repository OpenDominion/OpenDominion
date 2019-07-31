@extends('layouts.master')

@section('page-header', 'Statistics Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> Statistics Advisor</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title">Military</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Offensive Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Offensive Power:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}</strong>
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion))) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Offensive Power Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Offensive Ratio:</td>
                                        <td>
                                            <strong>{{ number_format(($militaryCalculator->getOffensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }}</strong>
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getOffensivePowerRaw($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }})</small>
                                            @endif
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
                                        <th colspan="2">Defensive Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Defensive Power:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}</strong>
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion))) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Power Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string(($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) - 1) * 100, 3, true) }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defense Ratio:</td>
                                        <td>
                                            <strong>{{ number_format(($militaryCalculator->getDefensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }}</strong>
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }})</small>
                                            @endif
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
                                        <th colspan="2">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Attacking success:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_attacking_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defending success:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_defending_success) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title">Operations</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Spy Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Spy Ratio (Offense):</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getSpyRatioRaw($selectedDominion, 'offense'), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spy Ratio (Defense):</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'defense'), 3) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getSpyRatioRaw($selectedDominion, 'defense'), 3) }})</small>
                                            @endif
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
                                        <th colspan="2">Wizard Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Wizard Ratio (Offense):</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getWizardRatioRaw($selectedDominion, 'offense'), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizard Ratio (Defense):</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'defense'), 3) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getWizardRatioRaw($selectedDominion, 'defense'), 3) }})</small>
                                            @endif
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
                                        <th colspan="2">&nbsp;</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Espionage Success:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_espionage_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Magic Success:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->stat_spell_success) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title">Population</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Current Population:</td>
                                        <td>
                                            <strong>{{ number_format($populationCalculator->getPopulation($selectedDominion)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Peasant Population:</td>
                                        <td>
                                            <strong>{{ number_format($selectedDominion->peasants) }}</strong>
                                            <small class="text-muted">({{ number_format((($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Military Population:</td>
                                        <td>
                                            <strong>{{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}</strong>
                                            <small class="text-muted">({{ number_format((100 - ($selectedDominion->peasants / $populationCalculator->getPopulation($selectedDominion)) * 100), 2) }}%)</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Max Population:</td>
                                        <td>
                                            <strong>{{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}</strong>
                                            @if ($populationCalculator->getMaxPopulationMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($populationCalculator->getMaxPopulationRaw($selectedDominion)) }} raw)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Population Multiplier:</td>
                                        <td>
                                            <strong>{{ number_string((($populationCalculator->getMaxPopulationMultiplier($selectedDominion) - 1) * 100), 3, true) }}%</strong>
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
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The statistics advisor gives you statistics regarding your current dominion state.</p>
                    <p>Ratio numbers are total number of units per acre of land.</p>
                </div>
            </div>
        </div>

    </div>

@endsection
