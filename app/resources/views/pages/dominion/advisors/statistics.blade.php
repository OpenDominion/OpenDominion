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

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Power</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Offensive Power:</td>
                                        <td>
                                            {{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}
                                            @if ($militaryCalculator->getOffensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getOffensivePowerRaw($selectedDominion)) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Power:</td>
                                        <td>
                                            {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
                                            @if ($militaryCalculator->getDefensivePowerMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getDefensivePowerRaw($selectedDominion)) }})</small>
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
                                        <th colspan="2">Ratios</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Spy Ratio (Offense):</td>
                                        <td>
                                            {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'offense'), 3) }}
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getSpyRatioRaw($selectedDominion, 'offense'), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spy Ratio (Defense):</td>
                                        <td>
                                            {{ number_format($militaryCalculator->getSpyRatio($selectedDominion, 'defense'), 3) }}
                                            @if ($militaryCalculator->getSpyRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getSpyRatioRaw($selectedDominion, 'defense'), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizard Ratio (Offense):</td>
                                        <td>
                                            {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getWizardRatioRaw($selectedDominion, 'defense'), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizard Ratio (Defense):</td>
                                        <td>
                                            {{ number_format($militaryCalculator->getWizardRatio($selectedDominion, 'offense'), 3) }}
                                            @if ($militaryCalculator->getWizardRatioMultiplier($selectedDominion) !== 1.0)
                                                <small class="text-muted">({{ number_format($militaryCalculator->getWizardRatioRaw($selectedDominion, 'defense'), 3) }})</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Defense Ratio:</td>
                                        <td>
                                            {{ number_format(($militaryCalculator->getDefensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 3) }}
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
                                        <th colspan="2">Black Ops</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Attacking Success:</td>
                                        <td>0{{--2 / 8 <small class="text-muted"><i>(25%)</i></small>--}}</td>
                                    </tr>
                                    <tr>
                                        <td>Defending Success:</td>
                                        <td>0</td>
                                    </tr>
                                    <tr>
                                        <td>Espionage Success:</td>
                                        <td>0</td>
                                    </tr>
                                    <tr>
                                        <td>Spell Success:</td>
                                        <td>0</td>
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
                    <p>The statistics advisor gives you some rudimentary numbers of statistics regarding your current state.</p>
                    <p>Ratio numbers are total number of units per acre of land.</p>
                </div>
            </div>
        </div>

    </div>

@endsection
