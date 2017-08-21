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
                                        <td>{{ number_format($militaryCalculator->getOffensivePowerRaw($selectedDominion)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Defensive Power:</td>
                                        <td>{{ number_format($militaryCalculator->getDefensivePowerRaw($selectedDominion)) }}</td>
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
                                        <td>Spy Ratio:</td>
                                        <td>{{ number_format($militaryCalculator->getSpyRatioRaw($selectedDominion), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Wizard Ratio:</td>
                                        <td>{{ number_format($militaryCalculator->getWizardRatioRaw($selectedDominion), 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Defense Ratio:</td>
                                        <td>{{ number_format(($militaryCalculator->getDefensivePowerRaw($selectedDominion) / $landCalculator->getTotalLand($selectedDominion)), 2) }}</td>
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
