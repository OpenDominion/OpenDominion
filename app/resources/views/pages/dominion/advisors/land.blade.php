@extends('layouts.master')

@section('page-header', 'Land Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-honeycomb"></i> Land Advisor</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Land Type</th>
                                <th class="text-center">Number</th>
                                <th class="text-center">% of total</th>
                                <th class="text-center">Barren</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landHelper->getLandTypes() as $landType)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $selectedDominion->race->home_land_type)
                                            <small class="text-muted"><i>(home)</i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($selectedDominion->{'land_' . $landType}) }}</td>
                                    <td class="text-center">{{ number_format((($selectedDominion->{'land_' . $landType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 2) }}%</td>
                                    <td class="text-center">{{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, $landType)) }}</td>
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
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming land breakdown</h3>
                </div>
                <div class="box-body no-padding">
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
                        @foreach ($landHelper->getLandTypes() as $landType)
                            <tr>
                                <td>
                                    {{ ucfirst($landType) }}
                                    @if ($landType === $selectedDominion->race->home_land_type)
                                        <small class="text-muted"><i>(home)</i></small>
                                    @endif
                                </td>
                                @for ($i = 0; $i < 12; $i++)
                                    <td class="text-center">
                                        @if ($explorationQueueService->getQueue($selectedDominion)[$landType][$i] === 0)
                                            -
                                        @else
                                            {{ number_format($explorationQueueService->getQueue($selectedDominion)[$landType][$i]) }}
                                        @endif
                                    </td>
                                @endfor
                                <td class="text-center">{{ number_format($explorationQueueService->getQueueTotalByLand($selectedDominion, $landType)) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
