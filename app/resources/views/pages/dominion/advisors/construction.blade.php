@extends('layouts.master')

@section('page-header', 'Construction Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Construction Advisor</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Building Type</th>
                                <th class="text-center">Number</th>
                                <th class="text-center">% of land</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buildingHelper->getBuildingTypes() as $buildingType)
                                <tr>
                                    <td>
                                        {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                        {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                                        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}"></i>
                                    </td>
                                    <td class="text-center">{{ number_format($selectedDominion->{'building_' . $buildingType}) }}</td>
                                    <td class="text-center">{{ number_format((($selectedDominion->{'building_' . $buildingType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming building breakdown</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 0; $i < 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Land Type</th>
                                @for ($i = 0; $i < 12; $i++)
                                    <th class="text-center">{{ ($i + 1) }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buildingHelper->getBuildingTypes() as $buildingType)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $buildingType)) }}</td>
                                    @for ($i = 0; $i < 12; $i++)
                                        <td class="text-center">
                                            @if ($queueService->getConstructionQueueAmount($selectedDominion, "building_{$buildingType}", $i) === 0)
                                                -
                                            @else
                                                {{ number_format($queueService->getConstructionQueueAmount($selectedDominion, "building_{$buildingType}", $i)) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">{{ number_format($queueService->getConstructionQueueTotalByResource($selectedDominion, "building_{$buildingType}")) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
