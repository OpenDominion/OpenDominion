@extends('layouts.master')

@section('page-header', 'Invasion Result')

@section('content')
    @php
        $boxColor = ($event->data['result']['success'] ? 'success' : 'danger');

        // todo: refactor/optimize
        // Invert box color if we are the target
        if ($event->target->id === $selectedDominion->id) {
            $boxColor = ($event->data['result']['success'] ? 'danger' : 'success');
        }
    @endphp
    <div class="row">
        <div class="col-sm-12 col-md-8 col-md-offset-2">
            <div class="box box-{{ $boxColor }}">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="ra ra-crossed-swords"></i>
                        {{ $event->source->name }} (#{{ $event->source->realm->number }})
                        vs
                        {{ $event->target->name }} (#{{ $event->target->realm->number }})
                    </h3>
                </div>
                <div class="box-bod no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-4">
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
                                                {!! $unitHelper->getUnitTypeIconHtml($unitType) !!}
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->source->race) }}">
                                                    {{ $event->source->race->units()->where('slot', $unitSlot)->first()->name }}
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

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            @if ($event->target->id === $selectedDominion->id)
                                                Your Losses
                                            @else
                                                {{ $event->target->name }} (#{{ $event->target->realm->number }})'s Losses
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (array_sum($event->data['defender']['unitsLost']) === 0)
                                        <tr>
                                            <td colspan="2" class="text-center">
                                                <em>None</em>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($event->data['defender']['unitsLost'] as $unitSlot => $amount)
                                            @if ($amount === 0)
                                                @continue
                                            @endif
                                            @php
                                                $unitType = (($unitSlot !== 'draftees') ? "unit{$unitSlot}" : 'draftees');
                                            @endphp
                                            <tr>
                                                <td>
                                                    {!! $unitHelper->getUnitTypeIconHtml($unitType) !!}
                                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $event->target->race) }}">
                                                        @if ($unitType === 'draftees')
                                                            Draftees
                                                        @else
                                                            {{ $event->target->race->units()->where('slot', $unitSlot)->first()->name }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ number_format($amount) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">
                                            @if ($event->target->id === $selectedDominion->id)
                                                Land Lost
                                            @else
                                                Land Conquered
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (!isset($event->data['attacker']['landConquered']))
                                        <tr>
                                            <td colspan="2" class="text-center">
                                                <em>None</em>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($event->data['attacker']['landConquered'] as $landType => $amount)
                                            @if ($amount === 0)
                                                @continue
                                            @endif
                                            <tr>
                                                <td>{{ ucfirst($landType) }}</td>
                                                <td>{{ number_format($amount) }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            @php
                                $recentlyInvadedCount = $militaryCalculator->getRecentlyInvadedCount($event->target);
                            @endphp

                            @if ($recentlyInvadedCount > 0)
                                <p class="text-center">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because the target was recently invaded, your prestige gains and their defensive losses are reduced.
                                    @else
                                        Because the target was recently invaded, {{ $event->source->name }} (# {{ $event->source->realm->number }})'s prestige gains and {{ $event->target->name }} (# {{ $event->target->realm->number }})'s defensive losses are reduced.
                                    @endif
                                </p>
                            @endif

                            @if (isset($event->data['result']['overwhelmed']))
                                <p class="text-center text-red">
                                    @if ($event->source->id === $selectedDominion->id)
                                        Because you were severely outmatched, you suffered extra casualties.
                                    @else
                                        Because {{ $event->source->name }} (# {{ $event->source->realm->number }}) was severely outmatched, they suffered extra casualties.
                                    @endif
                                </p>
                            @endif

                            {{-- Only show prestige gains if we are the attacker --}}
                            @if ($event->source->id === $selectedDominion->id)
                                @if (isset($event->data['attacker']['prestigeChange']))
                                    @php
                                        $prestigeChange = $event->data['attacker']['prestigeChange'];
                                    @endphp
                                    @if ($prestigeChange < 0)
                                        <p class="text-center text-red">
                                            You lost <b>{{ number_format(-$prestigeChange) }}</b> prestige.
                                        </p>
                                    @elseif ($prestigeChange > 0)
                                        <p class="text-center text-green">
                                            You gained <b>{{ number_format($prestigeChange) }}</b> prestige.
                                        </p>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
