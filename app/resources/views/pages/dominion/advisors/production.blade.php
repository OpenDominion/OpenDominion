@extends('layouts.master')

@section('page-header', 'Production Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-industry fa-fw"></i> Production Advisor</h3>
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
                                <th colspan="2">Production /hr</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Platinum:</td>
                                <td>{{ number_format($productionCalculator->getPlatinumProduction()) }}</td>
                            </tr>
                            <tr>
                                <td>Food:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Lumber:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Mana:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Ore:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Gems:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Research points:</td>
                                <td>NYI</td>
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
                                <th colspan="2">Consumption /hr</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Food Eaten:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Food Decayed:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Lumber Rotted:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Mana Drain:</td>
                                <td>NYI</td>
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
                                <th colspan="2">Net Change /hr</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="hidden-xs">
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <td>Food:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Lumber:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Mana:</td>
                                <td>NYI</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            Peasant change last hour: {{ number_format($selectedDominion->peasants_last_hour) }}<br>
            Maximum population: {{ number_format($populationCalculator->getMaxPopulation()) }}<br>
            Maximum peasant population: {{ number_format($populationCalculator->getMaxPopulation() - $populationCalculator->getPopulationMilitary()) }}<br>
            Jobs total: NYI<br>
            Jobs available: NYI<br>
            Opportunity cost of job overrun: NYI
        </div>
    </div>

@endsection
