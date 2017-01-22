@extends('layouts.master')

@section('page-header', 'Production Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-industry"></i> Production Advisor</h3>
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
                                <td>{{ number_format($productionCalculator->getFoodProduction()) }}</td>
                            </tr>
                            <tr>
                                <td>Lumber:</td>
                                <td>{{ number_format($productionCalculator->getLumberProduction()) }}</td>
                            </tr>
                            <tr>
                                <td>Mana:</td>
                                <td>{{ number_format($productionCalculator->getManaProduction()) }}</td>
                            </tr>
                            <tr>
                                <td class="nyi">Ore:</td>
                                <td class="nyi">NYI</td>
                            </tr>
                            <tr>
                                <td class="nyi">Gems:</td>
                                <td class="nyi">NYI</td>
                            </tr>
                            <tr>
                                <td class="nyi">Research points:</td>
                                <td class="nyi">NYI</td>
                            </tr>
                            <tr>
                                <td>Boats:</td>
                                <td>{{ number_format($productionCalculator->getBoatProduction()) }}</td>
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
                                <td>{{ number_format($productionCalculator->getFoodConsumption()) }}</td>
                            </tr>
                            <tr>
                                <td>Food Decayed:</td>
                                <td>{{ number_format($productionCalculator->getFoodDecay()) }}</td>
                            </tr>
                            <tr>
                                <td>Lumber Rotted:</td>
                                <td>{{ number_format($productionCalculator->getLumberDecay()) }}</td>
                            </tr>
                            <tr>
                                <td class="nyi">Mana Drain:</td>
                                <td class="nyi">NYI</td>
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
                                <td>{{ number_format($productionCalculator->getFoodNetChange()) }}</td>
                            </tr>
                            <tr>
                                <td>Lumber:</td>
                                <td>{{ number_format($productionCalculator->getLumberNetChange()) }}</td>
                            </tr>
                            <tr>
                                <td class="nyi">Mana:</td>
                                <td class="nyi">NYI</td>
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
            Jobs total: {{ number_format($populationCalculator->getEmploymentJobs()) }}<br>
            Jobs available: {{ number_format($populationCalculator->getEmploymentJobs() - $populationCalculator->getPopulationMilitary()) }}<br>
            <span class="nyi">Opportunity cost of job overrun: NYI</span>
        </div>
    </div>

@endsection
