@extends('layouts.master')

@section('page-header', 'Construction')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-building"></i> Construction</h3>
                </div>
                <form action="{{ route('dominion.construction') }}" method="post" role="form">
                    {!! csrf_field() !!}
                    <div class="box-body no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                                <col width="100">
                            </colgroup>

                            @foreach ($buildingHelper->getBuildingTypesByLandType($selectedDominion->race) as $landType => $buildingTypes)

                                @if (empty($buildingTypes))
                                    @continue
                                @endif

                                <thead>
                                    <tr>
                                        <th colspan="3">{{ ucfirst($landType) }} <span class="small">(Barren: {{ number_format($landCalculator->getTotalBarrenLandByLandType($landType)) }})</span></th>
                                    </tr>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned</th>
                                        <th class="text-center">Building</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($buildingTypes as $buildingType)
                                        <tr>
                                            <td>
                                                {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                                {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                                            </td>
                                            <td class="text-center">
                                                {{ $selectedDominion->{'building_' . $buildingType} }}
                                                <small>
                                                    ({{ number_format((($selectedDominion->{'building_' . $buildingType} / $landCalculator->getTotalLand()) * 100), 1) }}%)
                                                </small>
                                            </td>
                                            <td class="text-center">{{ number_format($dominionQueueService->getConstructionQueueTotalByBuilding($buildingType)) }}</td>
                                            <td class="text-center">
                                                <input type="number" name="construct[{{ $buildingType }}]" class="text-center" placeholder="0" min="0" max="{{ $buildingCalculator->getConstructionMaxAfford() }}" value="{{ old('construct.' . $buildingType) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            @endforeach

                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Build</button>
                        <div class="pull-right">
                            You have {{ number_format($landCalculator->getTotalLand()) }} acres of land.
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.construction') }}" class="pull-right">Construction Advisor</a>
                </div>
                <div class="box-body">
                    <p>You may also <a href="{{ route('dominion.destroy') }}">destroy</a> buildings if you wish.</p>
                    <p>Construction per building will come at a cost of {{ number_format($buildingCalculator->getConstructionPlatinumCost()) }} platinum and {{ number_format($buildingCalculator->getConstructionLumberCost()) }} lumber.</p>
                    <p>You can afford: {{ number_format($buildingCalculator->getConstructionMaxAfford()) }} buildings.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
