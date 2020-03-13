@extends('layouts.master')

@section('page-header', 'Production Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
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
                                        <td>
                                            @if ($platinumProduction = $productionCalculator->getPlatinumProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($platinumProduction) }}</span>
                                                @if ($productionCalculator->getPlatinumProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getPlatinumProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>
                                            @if ($foodProduction = $productionCalculator->getFoodProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($foodProduction) }}</span>
                                                @if ($productionCalculator->getFoodProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getFoodProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>
                                            @if ($lumberProduction = $productionCalculator->getLumberProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($lumberProduction) }}</span>
                                                @if ($productionCalculator->getLumberProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getLumberProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>
                                            @if ($manaProduction = $productionCalculator->getManaProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($manaProduction) }}</span>
                                                @if ($productionCalculator->getManaProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getManaProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>
                                            @if ($oreProduction = $productionCalculator->getOreProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($oreProduction) }}</span>
                                                @if ($productionCalculator->getOreProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getOreProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>
                                            @if ($gemProduction = $productionCalculator->getGemProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($gemProduction) }}</span>
                                                @if ($productionCalculator->getGemProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getGemProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Research points:</td>
                                        <td>
                                            @if ($techProduction = $productionCalculator->getTechProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($techProduction) }}</span>
                                                @if ($productionCalculator->getTechProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getTechProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boats:</td>
                                        <td>
                                            @if ($boatProduction = $productionCalculator->getBoatProduction($selectedDominion))
                                                <span class="text-green">+{{ number_format($boatProduction, 2) }}</span>
                                                @if ($productionCalculator->getBoatProductionMultiplier($selectedDominion) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getBoatProductionMultiplier($selectedDominion) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
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
                                        <th colspan="2">Consumption /hr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Food Eaten:</td>
                                        <td>
                                            @if ($foodConsumption = $productionCalculator->getFoodConsumption($selectedDominion))
                                                <span class="text-red">-{{ number_format($foodConsumption) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food Decayed:</td>
                                        <td>
                                            @if ($foodDecay = $productionCalculator->getFoodDecay($selectedDominion))
                                                <span class="text-red">-{{ number_format($foodDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber Rotted:</td>
                                        <td>
                                            @if ($lumberDecay = $productionCalculator->getLumberDecay($selectedDominion))
                                                <span class="text-red">-{{ number_format($lumberDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana Drain:</td>
                                        <td>
                                            @if ($manaDecay = $productionCalculator->getManaDecay($selectedDominion))
                                                <span class="text-red">-{{ number_format($manaDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
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
                                        <th colspan="2">Net Change /hr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="hidden-xs">
                                        <td colspan="2">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>
                                            @if (($foodNetChange = $productionCalculator->getFoodNetChange($selectedDominion)) < 0)
                                                <span class="text-red">{{ number_format($foodNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($foodNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>
                                            @if (($lumberNetChange = $productionCalculator->getLumberNetChange($selectedDominion)) < 0)
                                                <span class="text-red">{{ number_format($lumberNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($lumberNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>
                                            @if (($manaNetChange = $productionCalculator->getManaNetChange($selectedDominion)) < 0)
                                                <span class="text-red">{{ number_format($manaNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($manaNetChange) }}</span>
                                            @endif
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
                    <p>The production advisor tells you about your resource production, population and jobs.</p>
                    <p>
                        <b>Population</b><br>
                        Total: {{ number_format($populationCalculator->getPopulation($selectedDominion)) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion)) }}<br>
                        Peasants: {{ number_format($selectedDominion->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($selectedDominion) - $populationCalculator->getPopulationMilitary($selectedDominion)) }}
                        @if ($selectedDominion->peasants_last_hour < 0)
                            <span class="text-red">(<b>{{ number_format($selectedDominion->peasants_last_hour) }}</b> last hour)</span>
                        @elseif ($selectedDominion->peasants_last_hour > 0)
                            <span class="text-green">(<b>+{{ number_format($selectedDominion->peasants_last_hour) }}</b> last hour)</span>
                        @endif
                        <br>
                        Military: {{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}<br>
                        <br>
                        <b>Jobs</b><br>
                        Fulfilled: {{ number_format($populationCalculator->getPopulationEmployed($selectedDominion)) }} / {{ number_format($populationCalculator->getEmploymentJobs($selectedDominion)) }}<br>
                        @php($jobsNeeded = ($selectedDominion->peasants - $populationCalculator->getEmploymentJobs($selectedDominion)))
                        @if ($jobsNeeded < 0)
                            Available: {{ number_format(abs($jobsNeeded)) }}<br>
                            Opportunity cost of job overrun: <b>{{ number_format(2.7 * abs($jobsNeeded) * $productionCalculator->getPlatinumProductionMultiplier($selectedDominion)) }} platinum</b><br>
                            <br>
                            <i>"You should acquire additional peasants, since you have idle jobs.<br><br>Employed peasants pay their income tax in platinum to the dominion." -Production Advisor</i>
                        @elseif ($jobsNeeded === 0)
                            Available: 0<br>
                            No opportunity cost
                        @else
                            Needed: {{ number_format($jobsNeeded) }}<br>
                            Opportunity cost of job underrun: <b>{{ number_format(2.7 * $jobsNeeded * $productionCalculator->getPlatinumProductionMultiplier($selectedDominion)) }} platinum</b><br>
                            <br>
                            <i>"You should construct additional job buildings, since you have idle peasants.<br><br>Only employed peasants pay their income tax in platinum to the dominion." -Production Advisor</i>
                        @endif
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
