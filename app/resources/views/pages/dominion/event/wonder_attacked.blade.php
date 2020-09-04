@extends('layouts.master')

@section('page-header', 'Attack Result')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-sword"></i>
                        {{ $event->source->name }} (#{{ $event->source->realm->number }})
                        vs
                        {{ $event->target->wonder->name }}
                    </h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-6">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            @if ($event->source->id === $selectedDominion->id)
                                                Your Losses
                                            @else
                                                {{ $event->source->name }} (#{{ $event->source->realm->number }})'s Losses
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($event->data['attacker']['unitsLost'] as $unitSlot => $amount)
                                        @if ($amount === 0)
                                            @continue
                                        @endif
                                        @php
                                            $unitType = "unit{$unitSlot}";
                                        @endphp
                                        <tr>
                                            <td>
                                                {!! $unitHelper->getUnitTypeIconHtml($unitType, $event->source->race) !!}
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race) }}">
                                                    {{ $event->source->race->units->where('slot', $unitSlot)->first()->name }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ number_format($amount) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-6">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            Damage Dealt
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="text-center">
                                            {{ number_format($event->data['attacker']['op']) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            {{-- Only show research point gains if we are the attacker --}}
                            @if ($event->source->id === $selectedDominion->id)
                                @if (isset($event->data['attacker']['researchPoints']))
                                    <p class="text-center text-green">
                                        You gain <b>{{ number_format($event->data['attacker']['researchPoints']) }}</b> research points.
                                    </p>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
