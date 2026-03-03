@extends('layouts.master')

@section('page-header', 'Super Secret Debug Page™')

@section('content')
    <div class="alert alert-danger">
        <p>This is the Super Secret Debug Page&trade;, which is used for development and debugging purposes. This section will <strong>not</strong> be included in the final version. So don't get too used to seeing all this information directly.</p>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-4">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Networth Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        Realm Networth: <b>{{ number_format($networthCalculator->getRealmNetworth($selectedDominion->realm)) }}</b><br>
                        Dominion Networth: <b>{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</b><br>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Dominion</h3>
                </div>
                <div class="card-body">
                    @php
                        $dominion = clone $selectedDominion;
                        unset($dominion->race);
                        unset($dominion->realm);
                        unset($dominion->round);
                    @endphp
                    <pre>{{ print_r(json_decode($dominion), true) }}</pre>
                </div>
            </div>

        </div>
        <div class="col-sm-12 col-md-4">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Building Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($buildingCalculator, [
                            'getTotalBuildings',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Land Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($landCalculator, [
                            'getTotalLand',
                            'getTotalBarrenLand',
                            'getBarrenLandByLandType',
                            'getLandByLandType',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Military Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($militaryCalculator, [
                            'getOffensivePower',
                            'getOffensivePowerRaw',
                            'getOffensivePowerMultiplier',
                            'getOffensivePowerRatio',
                            'getOffensivePowerRatioRaw',
                            'getDefensivePower',
                            'getDefensivePowerRaw',
                            'getDefensivePowerMultiplier',
                            'getDefensivePowerRatio',
                            'getDefensivePowerRatioRaw',
                            'getMoraleMultiplier',
                            'getSpyRatio',
                            'getSpyRatioRaw',
                            'getSpyRatioMultiplier',
                            'getSpyStrengthRegen',
                            'getWizardRatio',
                            'getWizardRatioRaw',
                            'getWizardRatioMultiplier',
                            'getWizardStrengthRegen',
                            'getBoatCapacity',
                            'getBoatsProtected',
                            'getRecentlyInvadedCount',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Casualties Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($casualtiesCalculator, [
                            'getOffensiveCasualtiesMultiplier',
                            'getDefensiveCasualtiesMultiplier',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Population Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($populationCalculator, [
                            'getPopulation',
                            'getPopulationMilitary',
                            'getMaxPopulation',
                            'getMaxPopulationRaw',
                            'getMaxPopulationMultiplier',
                            'getMaxPopulationMilitaryBonus',
                            'getPopulationBirth',
                            'getPopulationBirthRaw',
                            'getPopulationBirthMultiplier',
                            'getPopulationPeasantGrowth',
                            'getPopulationDrafteeGrowth',
                            'getPopulationPeasantPercentage',
                            'getPopulationMilitaryPercentage',
                            'getEmploymentJobs',
                            'getPopulationEmployed',
                            'getEmploymentPercentage',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Production Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($productionCalculator, [
                            'getPlatinumProduction',
                            'getPlatinumProductionRaw',
                            'getPlatinumProductionMultiplier',
                            'getFoodProduction',
                            'getFoodProductionRaw',
                            'getFoodProductionMultiplier',
                            'getFoodConsumption',
                            'getFoodDecay',
                            'getFoodDecayMultiplier',
                            'getFoodNetChange',
                            'getLumberProduction',
                            'getLumberProductionRaw',
                            'getLumberProductionMultiplier',
                            'getLumberDecay',
                            'getLumberDecayMultiplier',
                            'getLumberNetChange',
                            'getManaProduction',
                            'getManaProductionRaw',
                            'getManaProductionMultiplier',
                            'getManaDecay',
                            'getManaDecayMultiplier',
                            'getManaNetChange',
                            'getOreProduction',
                            'getOreProductionRaw',
                            'getOreProductionMultiplier',
                            'getGemProduction',
                            'getGemProductionRaw',
                            'getGemProductionMultiplier',
                            'getBoatProduction',
                            'getBoatProductionRaw',
                            'getBoatProductionMultiplier',
                        ]) !!}
                    </p>
                </div>
            </div>

        </div>
        <div class="col-sm-12 col-md-4">

            {{--<div class="card">
                <div class="card-header">
                    <h3 class="card-title">Banking Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($bankingCalculator, [
                            'getResources',
                        ]) !!}
                    </p>
                </div>
            </div>--}}

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Construction Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($constructionCalculator, [
                            'getPlatinumCost',
                            'getPlatinumCostRaw',
                            'getPlatinumCostMultiplier',
                            'getLumberCost',
                            'getLumberCostRaw',
                            'getLumberCostMultiplier',
                            'getMaxAfford',
                            'getCostMultiplier',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Exploration Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($explorationCalculator, [
                            'getPlatinumCost',
                            'getDrafteeCost',
                            'getMaxAfford',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Rezoning Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($rezoningCalculator, [
                            'getPlatinumCost',
                            'getMaxAfford',
                            'getCostMultiplier',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Training Calculator</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($trainingCalculator, [
                            'getTrainingCostsPerUnit',
                            'getMaxTrainable',
                            'getSpecialistEliteCostMultiplier',
                            'getWizardCostMultiplier',
                        ]) !!}
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Guard Membership Service</h3>
                </div>
                <div class="card-body">
                    <p>
                        {!! \OpenDominion\Http\Controllers\DebugController::printMethodValues($guardMembershipService, [
                            'canJoinGuards',
                            'isRoyalGuardApplicant',
                            'isEliteGuardApplicant',
                            'isRoyalGuardMember',
                            'isEliteGuardMember',
                            'getHoursBeforeRoyalGuardMember',
                            'getHoursBeforeEliteGuardMember',
                            'getHoursBeforeLeaveRoyalGuard',
                            'getHoursBeforeLeaveEliteGuard',
                        ]) !!}
                    </p>
                </div>
            </div>

        </div>
    </div>

@endsection
