@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Military Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Units in training and home</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Home (Training)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unitHelper->getUnitTypes() as $unitType)
                                <tr>
                                    <td>
                                        {!! $unitHelper->getUnitTypeIconHtml($unitType, $target->race) !!}
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $target->race) }}">
                                            {{ $unitHelper->getUnitName($unitType, $target->race) }}
                                        </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <td class="text-center">
                                            @if ($queueService->getTrainingQueueAmount($target, "military_{$unitType}", $i) === 0)
                                                -
                                            @else
                                                {{ number_format($queueService->getTrainingQueueAmount($target, "military_{$unitType}", $i)) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($target->{'military_' . $unitType}) }}
                                        ({{ number_format($queueService->getTrainingQueueTotalByResource($target, "military_{$unitType}")) }})
                                    </td>
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
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Units returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col>
                            @for ($i = 1; $i <= 12; $i++)
                                <col width="20">
                            @endfor
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (range(1, 4) as $slot)
                                @php($unitType = ('unit' . $slot))
                                <tr>
                                    <td>
                                        {!! $unitHelper->getUnitTypeIconHtml($unitType, $target->race) !!}
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $target->race) }}">
                                            {{ $unitHelper->getUnitName($unitType, $target->race) }}
                                        </span>
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <td class="text-center">
                                            @if ($queueService->getInvasionQueueAmount($target, "military_{$unitType}", $i) === 0)
                                                -
                                            @else
                                                {{ number_format($queueService->getInvasionQueueAmount($target, "military_{$unitType}", $i)) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        {{ number_format($queueService->getInvasionQueueTotalByResource($target, "military_{$unitType}")) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
