@extends('layouts.master')

@section('page-header', 'Super Secret Debug Page')

@section('content')
    <div class="box box">
        <div class="box-header with-border">
            <h3 class="box-title">Population Calculator</h3>
        </div>
        <div class="box-body">
            <p>
                {!! printCalculatorMethodValues($populationCalculator, [
                    'getPopulation',
                    'getPopulationMilitary',
                    'getMaxPopulation',
                    'getMaxPopulationRaw',
                    'getMaxPopulationMultiplier',
                    'getPopulationBirth',
                    'getPopulationBirthRaw',
                    'getPopulationBirthMultiplier',
                    'getPopulationPeasantGrowth',
                    'getPopulationDrafteeGrowth',
                    'getPopulationPeasantPercentage',
                    'getPopulationMilitaryPercentage',
                    'getPopulationMilitaryMaxTrainable',
                    'getEmploymentJobs',
                    'getPopulationEmployed',
                    'getEmploymentPercentage',
                ]) !!}
            </p>
        </div>
    </div>

    <div class="box box">
        <div class="box-header with-border">
            <h3 class="box-title">Production Calculator</h3>
        </div>
        <div class="box-body">
            <p>
                {!! printCalculatorMethodValues($productionCalculator, [
                    'getPlatinumProduction',
                    'getPlatinumProductionRaw',
                    'getPlatinumProductionMultiplier',
                    'getFoodProduction',
                    'getFoodProductionRaw',
                    'getFoodProductionMultiplier',
                    'getFoodConsumption',
                    'getFoodDecay',
                    'getFoodNetChange',
                    'getLumberProduction',
                    'getLumberProductionRaw',
                    'getLumberProductionMultiplier',
                    'getLumberDecay',
                ]) !!}
            </p>
        </div>
    </div>

    <div class="box box">
        <div class="box-header with-border">
            <h3 class="box-title">Dominion</h3>
        </div>
        <div class="box-body">
            <pre>{{ print_r(json_decode($selectedDominion), true) }}</pre>
        </div>
    </div>
@endsection
