@extends('layouts.master')

@php
    $data = $infoMapper->mapBuildings($selectedDominion);
    $totalBarrenLand = array_get($data, 'barren_land', 0);
    $totalLand = array_get($data, 'total_land', 250);
@endphp

@section('page-header', 'Construction')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Construct Buildings</h3>
                </div>
                <form action="{{ route('dominion.construct') }}" method="post" role="form">
                    @csrf
                    <div class="box-body no-padding">
                        <div class="row">

                            <div class="col-md-12 col-lg-6">
                                @php
                                    /** @var \Illuminate\Support\Collection $buildingTypesLeft */
                                    $landTypesBuildingTypes = collect($buildingHelper->getBuildingTypesByRace($selectedDominion->race))->filter(function ($buildingTypes, $landType) {
                                        return in_array($landType, ['plain', 'mountain', 'swamp'], true);
                                    });
                                @endphp

                                @include('partials.dominion.construction.table')
                            </div>

                            <div class="col-md-12 col-lg-6">
                                @php
                                    /** @var \Illuminate\Support\Collection $buildingTypesLeft */
                                    $landTypesBuildingTypes = collect($buildingHelper->getBuildingTypesByRace($selectedDominion->race))->filter(function ($buildingTypes, $landType) {
                                        return in_array($landType, ['cavern', 'forest', 'hill', 'water'], true);
                                    });
                                @endphp

                                @include('partials.dominion.construction.table')
                            </div>

                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Build</button>
                        <div class="pull-right">
                            You have {{ number_format($landCalculator->getTotalLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalLand($selectedDominion)) }} of land.
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.construct') }}#advisor" class="pull-right">Construction Advisor</a>
                </div>
                <div class="box-body">
                    <p>Construction will let you construct additional buildings and will take <b>12 hours</b> to process.</p>
                    <p>Each building houses 15 people and employs 20 peasants unless noted otherwise. Barren land houses 5 people.</p>
                    <p>Construction per building will come at a cost of 1 acre of barren land of the building type, {{ number_format($constructionCalculator->getPlatinumCost($selectedDominion)) }} platinum and {{ number_format($constructionCalculator->getLumberCost($selectedDominion)) }} lumber.</p>
                    <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land, {{ number_format($selectedDominion->resource_platinum) }} platinum and {{ number_format($selectedDominion->resource_lumber) }} lumber.</p>
                    @if ($selectedDominion->discounted_land)
                        <p>Additionally, {{ $selectedDominion->discounted_land }} acres from invasion can be built at reduced cost of {{ number_format(rceil($constructionCalculator->getPlatinumCost($selectedDominion) * $constructionCalculator->getDiscountedLandMultiplier($selectedDominion))) }} platinum and {{ number_format(rceil($constructionCalculator->getLumberCost($selectedDominion) * $constructionCalculator->getDiscountedLandMultiplier($selectedDominion))) }} lumber.</p>
                    @endif
                    <p>You can afford to construct <b>{{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('building', $constructionCalculator->getMaxAfford($selectedDominion)) }}</b> at that rate.</p>
                    <p><a href="{{ route('dominion.destroy') }}" class="btn btn-danger">Destroy Buildings</a></p>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6" id="advisor">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Construction Advisor</h3>
                    <span class="pull-right">Barren Land: <strong>{{ number_format($totalBarrenLand) }}</strong> <small>({{ number_format(($totalBarrenLand / $totalLand) * 100, 2) }}%)</small></span>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.construction-constructed-table', ['data' => $data])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming building breakdown</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.construction-constructing-table', ['data' => $data])
                </div>
            </div>
        </div>

    </div>
@endsection
