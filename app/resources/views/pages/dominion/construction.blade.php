@extends('layouts.master')

@section('page-header', 'Construction')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Construct Buildings</h3>
                </div>
                <form action="{{ route('dominion.construct') }}" method="post" role="form">
                    {!! csrf_field() !!}
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                                <col width="100">
                            </colgroup>

                            @foreach ($buildingHelper->getBuildingTypesByRace($selectedDominion->race) as $landType => $buildingTypes)

                                @if (empty($buildingTypes))
                                    @continue
                                @endif

                                <thead>
                                    <tr>
                                        <th colspan="3">{{ ucfirst($landType) }} <span class="small">(Barren: {{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, $landType)) }})</span></th>
                                    </tr>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned</th>
                                        <th class="text-center">Constructing</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($buildingTypes as $buildingType)
                                        <tr>
                                            <td>
                                                {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                                {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                                                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}"></i>
                                            </td>
                                            <td class="text-center">
                                                {{ $selectedDominion->{'building_' . $buildingType} }}
                                                <small>
                                                    ({{ number_format((($selectedDominion->{'building_' . $buildingType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 1) }}%)
                                                </small>
                                            </td>
                                            <td class="text-center">{{ number_format($constructionQueueService->getQueueTotalByBuilding($selectedDominion, $buildingType)) }}</td>
                                            <td class="text-center">
                                                <input type="number" name="construct[{{ $buildingType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $constructionCalculator->getMaxAfford($selectedDominion) }}" value="{{ old('construct.' . $buildingType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            @endforeach

                        </table>
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
                    <a href="{{ route('dominion.advisors.construct') }}" class="pull-right">Construction Advisor</a>
                </div>
                <div class="box-body">
                    <p>Construction will let you construct additional buildings and will take <b>12 hours</b> to process.</p>
                    <p>Construction per building will come at a cost of 1 acre of barren land of the building type, {{ number_format($constructionCalculator->getPlatinumCost($selectedDominion)) }} platinum and {{ number_format($constructionCalculator->getLumberCost($selectedDominion)) }} lumber.</p>
                    <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land, {{ number_format($selectedDominion->resource_platinum) }} platinum and {{ number_format($selectedDominion->resource_lumber) }} lumber.</p>
                    <p>You can afford to construct <b>{{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('building', $constructionCalculator->getMaxAfford($selectedDominion)) }}</b> at that rate.</p>
                    <p>You may also <a href="{{ route('dominion.destroy') }}">destroy buildings</a> if you wish.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
