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
                                        <th class="text-center">Building</th>
                                        <th class="text-center">Build</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($buildingTypes as $buildingType)
                                        <tr>
                                            <td>
                                                {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                                {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}<br>
                                                <span class="text-muted"><i>{{ $buildingHelper->getBuildingHelpString($buildingType) }}</i></span>
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
                            You have {{ number_format($landCalculator->getTotalLand($selectedDominion)) }} acres of land.
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
                    <p>Construction per building will come at a cost of {{ number_format($constructionCalculator->getPlatinumCost($selectedDominion)) }} platinum and {{ number_format($constructionCalculator->getLumberCost($selectedDominion)) }} lumber.</p>
                    <p>You can afford: {{ number_format($constructionCalculator->getMaxAfford($selectedDominion)) }} buildings.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
