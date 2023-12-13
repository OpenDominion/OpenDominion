@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Statistics Advisor';
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
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> {{ $pageHeader }}</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title">Espionage</h4>
                            </div>
                        </div>
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
                                            <strong>{{ number_format($militaryCalculator->getSpyRatio($target, 'offense'), 3) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($target, 'offense') !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($target, 'offense') - 1) * 100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spy Ratio (Defense):</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyRatio($target, 'defense'), 3) }}</strong>
                                            @if ($militaryCalculator->getSpyRatioMultiplier($target, 'defense') !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getSpyRatioMultiplier($target, 'defense') - 1) * 100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Strength Recovery:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getSpyStrengthRegen($target), 2) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Espionage Success:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_espionage_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Espionage Failure:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_espionage_failure) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Success Rate:</td>
                                        <td>
                                            <strong>
                                                @if (($target->stat_espionage_success + $target->stat_espionage_success) > 0)
                                                    {{ number_format($target->stat_espionage_success / ($target->stat_espionage_success + $target->stat_espionage_failure) * 100) }}%
                                                @else
                                                    0%
                                                @endif
                                            </strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spies Executed:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_spies_executed) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Spies Lost:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_spies_lost) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="25%">
                                    <col width="25%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Black Ops</th>
                                        <th>Offense</th>
                                        <th>Defense</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Draftees Assassinated:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_assassinate_draftees_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_assassinate_draftees_damage_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizards Assassinated:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_assassinate_wizards_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_assassinate_wizards_damage_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Snare Impact:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_magic_snare_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_magic_snare_damage_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Boats Sunk:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_sabotage_boats_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_sabotage_boats_damage_received) }}</strong>
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
                                        <th colspan="2">Theft</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Platinum Stolen:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_total_platinum_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lumber Stolen:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_total_lumber_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Food Stolen:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_total_food_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Mana Stolen:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_total_mana_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Ore Stolen:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_total_ore_stolen) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Gems Stolen:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_total_gems_stolen) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-sm-12">
                            <div class="box-header with-border">
                                <h4 class="box-title">Magic</h4>
                            </div>
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
                                            <strong>{{ number_format($militaryCalculator->getWizardRatio($target, 'offense'), 3) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($target, 'offense') !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($target, 'offense') - 1) * 100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizard Ratio (Defense):</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardRatio($target, 'defense'), 3) }}</strong>
                                            @if ($militaryCalculator->getWizardRatioMultiplier($target, 'defense') !== 1.0)
                                                <small class="text-muted">({{ number_format(($militaryCalculator->getWizardRatioMultiplier($target, 'defense') - 1) * 100, 2) }}%)</small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Strength Recovery:</td>
                                        <td>
                                            <strong>{{ number_format($militaryCalculator->getWizardStrengthRegen($target), 2) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Magic Success:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_spell_success) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Magic Failure:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_spell_failure) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Success Rate:</td>
                                        <td>
                                            <strong>
                                                @if (($target->stat_spell_success + $target->stat_spell_failure) > 0)
                                                    {{ number_format($target->stat_spell_success / ($target->stat_spell_success + $target->stat_spell_failure) * 100) }}%
                                                @else
                                                    0%
                                                @endif
                                            </strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizards Executed:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_wizards_executed) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Wizards Lost:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_wizards_lost) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="25%">
                                    <col width="25%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Black Ops</th>
                                        <th>Offense</th>
                                        <th>Defense</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Spies Disbanded:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_disband_spies_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_disband_spies_damage_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Fireball Damage:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_fireball_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_fireball_damage_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lightning Bolt Damage:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_lightning_bolt_damage) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_lightning_bolt_damage_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Earthquake Hours:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_earthquake_hours) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_earthquake_hours_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Great Flood Hours:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_great_flood_hours) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_great_flood_hours_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Insect Swarm Hours:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_insect_swarm_hours) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_insect_swarm_hours_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Plague Hours:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_plague_hours) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_plague_hours_received) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Cyclone Damage:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_cyclone_damage) }}</strong>
                                        </td>
                                        <td>
                                            --
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="25%">
                                    <col width="25%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Spell Reflect</th>
                                        <th>Offense</th>
                                        <th>Defense</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Spells Reflected:</td>
                                        <td>
                                            <strong>{{ number_format($target->stat_spells_deflected) }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ number_format($target->stat_spells_reflected) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <table class="table">
                                <colgroup>
                                    <col width="75%">
                                    <col width="25%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Vulnerability</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Fireball:</td>
                                        <td>
                                            <strong>{{ $opsCalculator->getPeasantsVulnerableModifier($target) * 100 }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Peasants Vulnerable:</td>
                                        <td>
                                            <strong>{{ number_format($opsCalculator->getPeasantsVulnerable($target)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Peasants Protected:</td>
                                        <td>
                                            <strong>{{ number_format($opsCalculator->getPeasantsProtected($target)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lightning Bolt:</td>
                                        <td>
                                            <strong>{{ $opsCalculator->getImprovementsVulnerableModifier($target) * 100 }}%</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Improvements Vulnerable:</td>
                                        <td>
                                            <strong>{{ number_format($opsCalculator->getImprovementsVulnerable($target)) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Improvements Protected:</td>
                                        <td>
                                            <strong>{{ number_format($opsCalculator->getImprovementsProtected($target)) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="box-header with-border">
                                        <h4 class="box-title">Land Gain</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
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
                                                <td>Conquered:</td>
                                                <td>
                                                    <strong>{{ number_format($target->stat_total_land_conquered) }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Explored:</td>
                                                <td>
                                                    <strong>{{ number_format($target->stat_total_land_explored) }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Lost:</td>
                                                <td>
                                                    <strong>{{ number_format($target->stat_total_land_lost) }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Highest Land:</td>
                                                <td>
                                                    <strong>{{ number_format($target->highest_land_achieved) }}</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="box-header with-border">
                                        <h4 class="box-title">Wonders</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-12">
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
                                                <td>Wonder damage:</td>
                                                <td>
                                                    <strong>{{ number_format($target->stat_wonder_damage) }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Wonders destroyed:</td>
                                                <td>
                                                    <strong>{{ number_format($target->stat_wonders_destroyed) }}</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
