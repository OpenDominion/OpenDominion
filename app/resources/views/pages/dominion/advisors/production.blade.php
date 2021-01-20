@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Production Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-industry"></i> {{ $pageHeader }}</h3>
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
                                            @if ($platinumProduction = $productionCalculator->getPlatinumProduction($target))
                                                <span class="text-green">+{{ number_format($platinumProduction) }}</span>
                                                @if ($productionCalculator->getPlatinumProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getPlatinumProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>
                                            @if ($foodProduction = $productionCalculator->getFoodProduction($target))
                                                <span class="text-green">+{{ number_format($foodProduction) }}</span>
                                                @if ($productionCalculator->getFoodProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getFoodProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>
                                            @if ($lumberProduction = $productionCalculator->getLumberProduction($target))
                                                <span class="text-green">+{{ number_format($lumberProduction) }}</span>
                                                @if ($productionCalculator->getLumberProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getLumberProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>
                                            @if ($manaProduction = $productionCalculator->getManaProduction($target))
                                                <span class="text-green">+{{ number_format($manaProduction) }}</span>
                                                @if ($productionCalculator->getManaProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getManaProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>
                                            @if ($oreProduction = $productionCalculator->getOreProduction($target))
                                                <span class="text-green">+{{ number_format($oreProduction) }}</span>
                                                @if ($productionCalculator->getOreProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getOreProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>
                                            @if ($gemProduction = $productionCalculator->getGemProduction($target))
                                                <span class="text-green">+{{ number_format($gemProduction) }}</span>
                                                @if ($productionCalculator->getGemProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getGemProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Research points:</td>
                                        <td>
                                            @if ($techProduction = $productionCalculator->getTechProduction($target))
                                                <span class="text-green">+{{ number_format($techProduction) }}</span>
                                                @if ($productionCalculator->getTechProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getTechProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
                                                @endif
                                            @else
                                                0
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boats:</td>
                                        <td>
                                            @if ($boatProduction = $productionCalculator->getBoatProduction($target))
                                                <span class="text-green">+{{ number_format($boatProduction, 2) }}</span>
                                                @if ($productionCalculator->getBoatProductionMultiplier($target) != 1)
                                                    <small class="text-muted">({{ number_format(($productionCalculator->getBoatProductionMultiplier($target) - 1) * 100, 2) }}%)</small>
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
                                            @if ($foodConsumption = $productionCalculator->getFoodConsumption($target))
                                                <span class="text-red">-{{ number_format($foodConsumption) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food Decayed:</td>
                                        <td>
                                            @if ($foodDecay = $productionCalculator->getFoodDecay($target))
                                                <span class="text-red">-{{ number_format($foodDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber Rotted:</td>
                                        <td>
                                            @if ($lumberDecay = $productionCalculator->getLumberDecay($target))
                                                <span class="text-red">-{{ number_format($lumberDecay) }}</span>
                                            @else
                                                <span class="text-green">+0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana Drain:</td>
                                        <td>
                                            @if ($manaDecay = $productionCalculator->getManaDecay($target))
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
                                            @if (($foodNetChange = $productionCalculator->getFoodNetChange($target)) < 0)
                                                <span class="text-red">{{ number_format($foodNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($foodNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>
                                            @if (($lumberNetChange = $productionCalculator->getLumberNetChange($target)) < 0)
                                                <span class="text-red">{{ number_format($lumberNetChange) }}</span>
                                            @else
                                                <span class="text-green">+{{ number_format($lumberNetChange) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>
                                            @if (($manaNetChange = $productionCalculator->getManaNetChange($target)) < 0)
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
                    <p>The production advisor tells you about your resource production, population, and jobs.</p>
                    <table class="table">
                        <colgroup>
                            <col width="50%">
                            <col width="50%">
                        </colgroup>
                        <tbody>
                            <tr>
                                <td>Population:</td>
                                <td>{{ number_format($populationCalculator->getPopulation($target)) }} / {{ number_format($populationCalculator->getMaxPopulation($target)) }}</td>
                            </tr>
                            <tr>
                                <td>Peasants:</td>
                                <td>{{ number_format($target->peasants) }} / {{ number_format($populationCalculator->getMaxPopulation($target) - $populationCalculator->getPopulationMilitary($target)) }}</td>
                            </tr>
                            <tr>
                                <td>Peasant Change:</td>
                                <td>
                                    @if ($target->peasants_last_hour < 0)
                                        <span class="text-red">{{ number_format($target->peasants_last_hour) }} last hour</span>
                                    @elseif ($target->peasants_last_hour > 0)
                                        <span class="text-green">+{{ number_format($target->peasants_last_hour) }} last hour</span>
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Military:</td>
                                <td>{{ number_format($populationCalculator->getPopulationMilitary($target)) }}</td>
                            </tr>
                            <tr>
                                <td>Jobs:</td>
                                <td>{{ number_format($populationCalculator->getPopulationEmployed($target)) }} / {{ number_format($populationCalculator->getEmploymentJobs($target)) }}</td>
                            </tr>
                            @php($jobsNeeded = ($target->peasants - $populationCalculator->getEmploymentJobs($target)))
                            @if ($jobsNeeded < 0)
                                <tr>
                                    <td>Jobs Available:</td>
                                    <td>{{ number_format(abs($jobsNeeded)) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="You should acquire additional peasants, since you have idle jobs.<br>Employed peasants pay their income tax in platinum to the dominion.">
                                            Opportunity Cost:
                                        </span>
                                    </td>
                                    <td>{{ number_format(2.7 * abs($jobsNeeded) * $productionCalculator->getPlatinumProductionMultiplier($target)) }} platinum</td>
                                </tr>
                            @elseif ($jobsNeeded === 0)
                                <tr>
                                    <td>Jobs Available:</td>
                                    <td>0</td>
                                </tr>
                                <tr>
                                    <td>Opportunity Cost:</td>
                                    <td>0</td>
                                </tr>
                            @else
                                <tr>
                                    <td>Jobs Needed:</td>
                                    <td>{{ number_format($jobsNeeded) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="You should construct additional job buildings, since you have idle peasants.<br>Only employed peasants pay their income tax in platinum to the dominion.">
                                            Opportunity Cost:
                                        </span>
                                    </td>
                                    <td>{{ number_format(2.7 * $jobsNeeded * $productionCalculator->getPlatinumProductionMultiplier($target)) }} platinum</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    <!--
                    @if ($target->infamy > 0)
                        <p>You have <b>{{ $target->infamy }}</b> infamy, which is increasing your platinum production by {{ number_format(7.5 * $productionCalculator->getInfamyBonus($target->infamy), 2) }}% and gem/lumber/ore production by {{ number_format(3 * $productionCalculator->getInfamyBonus($target->infamy), 2) }}%.</p>
                    @endif
                    -->
                </div>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-mining-diamonds"></i> Resource Expenditure</h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped">
                            <colgroup>
                                <col>
                                <col width="11%">
                                <col width="11%">
                                <col width="11%">
                                <col width="11%">
                                <col width="11%">
                                <col width="11%">
                                <col width="11%">
                                <col width="11%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Resource</th>
                                    <th>Produced</th>
                                    <th>Stolen</th>
                                    <th>Decayed</th>
                                    <th>Invested</th>
                                    <th>Construction</th>
                                    <th>Exploration</th>
                                    <th>Rezoning</th>
                                    <th>Training</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Platinum</td>
                                    <td>{{ number_format($target->stat_total_platinum_production) }}</td>
                                    <td>{{ number_format($target->stat_total_platinum_stolen) }}</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_platinum_spent_investment) }}</td>
                                    <td>{{ number_format($target->stat_total_platinum_spent_construction) }}</td>
                                    <td>{{ number_format($target->stat_total_platinum_spent_exploration) }}</td>
                                    <td>{{ number_format($target->stat_total_platinum_spent_rezoning) }}</td>
                                    <td>{{ number_format($target->stat_total_platinum_spent_training) }}</td>
                                </tr>
                                <tr>
                                    <td>Food</td>
                                    <td>{{ number_format($target->stat_total_food_production) }}</td>
                                    <td>{{ number_format($target->stat_total_food_stolen) }}</td>
                                    <td>{{ number_format($target->stat_total_food_decay) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                </tr>
                                <tr>
                                    <td>Lumber</td>
                                    <td>{{ number_format($target->stat_total_lumber_production) }}</td>
                                    <td>{{ number_format($target->stat_total_lumber_stolen) }}</td>
                                    <td>{{ number_format($target->stat_total_lumber_decay) }}</td>
                                    <td>{{ number_format($target->stat_total_lumber_spent_investment) }}</td>
                                    <td>{{ number_format($target->stat_total_lumber_spent_construction) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_lumber_spent_training) }}</td>
                                </tr>
                                <tr>
                                    <td>Mana</td>
                                    <td>{{ number_format($target->stat_total_mana_production) }}</td>
                                    <td>{{ number_format($target->stat_total_mana_stolen) }}</td>
                                    <td>{{ number_format($target->stat_total_mana_decay) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_mana_spent_training) }}</td>
                                </tr>
                                <tr>
                                    <td>Ore</td>
                                    <td>{{ number_format($target->stat_total_ore_production) }}</td>
                                    <td>{{ number_format($target->stat_total_ore_stolen) }}</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_ore_spent_investment) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_ore_spent_training) }}</td>
                                </tr>
                                <tr>
                                    <td>Gems</td>
                                    <td>{{ number_format($target->stat_total_gem_production) }}</td>
                                    <td>{{ number_format($target->stat_total_gems_stolen) }}</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_gems_spent_investment) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>{{ number_format($target->stat_total_gems_spent_training) }}</td>
                                </tr>
                                <tr>
                                    <td>Research Points</td>
                                    <td>{{ number_format($target->stat_total_tech_production) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                </tr>
                                <tr>
                                    <td>Boats</td>
                                    <td>{{ number_format($target->stat_total_boat_production) }}</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>--</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
