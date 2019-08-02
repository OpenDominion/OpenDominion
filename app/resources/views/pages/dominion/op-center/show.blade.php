@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'clear_sight');
                @endphp

                @slot('title', ('Status Screen (' . $dominion->name . ')'))
                @slot('titleIconClass', 'fa fa-bar-chart')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Clear Sight' to reveal information.</p>
                @else
                    @php
                        $race = OpenDominion\Models\Race::findOrFail($infoOp->data['race_id']);
                    @endphp

                    @slot('noPadding', true)

                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Overview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Ruler:</td>
                                        <td>{{ $infoOp->data['ruler_name'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Race:</td>
                                        <td>{{ $race->name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Land:</td>
                                        <td>
                                            {{ number_format($infoOp->data['land']) }}
                                            <span class="{{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion) }}">
                                                ({{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}%)
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Peasants:</td>
                                        <td>{{ number_format($infoOp->data['peasants']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Employment:</td>
                                        <td>{{ number_format($infoOp->data['employment'], 2) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Networth:</td>
                                        <td>{{ number_format($infoOp->data['networth']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Prestige:</td>
                                        <td>{{ number_format($infoOp->data['prestige']) }}</td>
                                    </tr>
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
                                        <th colspan="2">Resources</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Platinum:</td>
                                        <td>{{ number_format($infoOp->data['resource_platinum']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>{{ number_format($infoOp->data['resource_food']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>{{ number_format($infoOp->data['resource_lumber']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>{{ number_format($infoOp->data['resource_mana']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>{{ number_format($infoOp->data['resource_ore']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>{{ number_format($infoOp->data['resource_gems']) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="nyi">Research Points:</td>
                                        <td class="nyi">{{ number_format($infoOp->data['resource_tech']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Boats:</td>
                                        <td>{{ number_format($infoOp->data['resource_boats']) }}</td>
                                    </tr>
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
                                        <th colspan="2">Military</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Morale:</td>
                                        <td>{{ number_format($infoOp->data['morale']) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Draftees:</td>
                                        <td>{{ number_format($infoOp->data['military_draftees']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $race->units->get(0)->name }}:</td>
                                        <td>{{ number_format($infoOp->data['military_unit1']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $race->units->get(1)->name }}:</td>
                                        <td>{{ number_format($infoOp->data['military_unit2']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $race->units->get(2)->name }}:</td>
                                        <td>{{ number_format($infoOp->data['military_unit3']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $race->units->get(3)->name }}:</td>
                                        <td>{{ number_format($infoOp->data['military_unit4']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Spies:</td>
                                        <td>???</td>
                                    </tr>
                                    <tr>
                                        <td>Wizards:</td>
                                        <td>???</td>
                                    </tr>
                                    <tr>
                                        <td>ArchMages:</td>
                                        <td>???</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @php
                        $recentlyInvadedCount = (isset($infoOp->data['recently_invaded_count']) ? (int)$infoOp->data['recently_invaded_count'] : 0);
                    @endphp

                    @if ($recentlyInvadedCount > 0)
                        <p class="text-center" style="margin-bottom: 0.5em;">
                            @if ($recentlyInvadedCount >= 5)
                                This dominion has been invaded <strong><em>extremely heavily</em></strong> in recent times.
                            @elseif ($recentlyInvadedCount >= 3)
                                This dominion has been invaded <strong>heavily</strong> in recent times.
                            @else
                                This dominion has been invaded in recent times.
                            @endif
                        </p>
                    @endif
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->updated_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="spell" value="clear_sight">
                            <button type="submit" class="btn btn-sm btn-primary">Clear Sight ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'clear_sight')) }} mana)</button>
                        </form>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This page contains the data that your realmies have gathered about dominion <b>{{ $dominion->name }}</b> from realm {{ $dominion->realm->name }} (#{{ $dominion->realm->number }}).</p>

                    <p>Sections marked as <span class="label label-warning">stale</span> contain data from the previous hour (or earlier) and should be considered inaccurate. Recast your info ops before performing any offensive operations during this hour.</p>

                    {{--<p>Estimated stats:</p>
                    <p>
                        OP: ??? <abbr title="Not yet implemented" class="label label-danger">NYI</abbr><br>
                        DP: ??? <abbr title="Not yet implemented" class="label label-danger">NYI</abbr><br>
                        Land: {{ $infoOpService->getLandString($selectedDominion->realm, $dominion) }}<br>
                        Networth: {{ $infoOpService->getNetworthString($selectedDominion->realm, $dominion) }}<br>
                    </p>--}}

                    {{-- todo: invade button --}}
                </div>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'revelation');
                @endphp

                @slot('title', 'Active Spells')
                @slot('titleIconClass', 'ra ra-magic-wand')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Revelation' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                            <col width="200s">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th>Effect</th>
                                <th class="text-center">Duration</th>
                                <th class="text-center">Cast By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($infoOp->data as $spell)
                                @php
                                    $spellInfo = $spellHelper->getSpellInfo($spell['spell'], $dominion->race);
                                    $castByDominion = OpenDominion\Models\Dominion::with('realm')->findOrFail($spell['cast_by_dominion_id']);
                                @endphp
                                <tr>
                                    <td>{{ $spellInfo['name'] }}</td>
                                    <td>{{ $spellInfo['description'] }}</td>
                                    <td class="text-center">{{ $spell['duration'] }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('dominion.realm', $castByDominion->realm->number) }}">{{ $castByDominion->name }} (#{{ $castByDominion->realm->number }})</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->updated_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="spell" value="revelation">
                            <button type="submit" class="btn btn-sm btn-primary">Revelation ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'revelation')) }} mana)</button>
                        </form>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'castle_spy');
                @endphp

                @slot('title', 'Improvements')
                @slot('titleIconClass', 'fa fa-arrow-up')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Castle Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <td>Part</td>
                                <td>Rating</td>
                                <td class="text-center">Invested</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($improvementHelper->getImprovementTypes() as $improvementType)
                                <tr>
                                    <td>
                                        {{ ucfirst($improvementType) }}
                                        {!! $improvementHelper->getImprovementImplementedString($improvementType) !!}
                                        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType) }}"></i>
                                    </td>
                                    <td>
                                        {{ sprintf(
                                            $improvementHelper->getImprovementRatingString($improvementType),
                                            number_format((array_get($infoOp->data, "{$improvementType}.rating") * 100), 2)
                                        ) }}
                                    </td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "{$improvementType}.points")) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->updated_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="castle_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Castle Spy</button>
                        </form>
                    </div>
                @endslot
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'barracks_spy');
                @endphp

                @slot('title', 'Units in training and home')
                @slot('titleIconClass', 'ra ra-sword')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Barracks Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

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
                            <tr>
                                <td>Draftees</td>
                                <td colspan="12">&nbsp;</td>
                                <td class="text-center">
                                    {{ number_format(array_get($infoOp->data, 'units.home.draftees', 0)) }}
                                </td>
                            </tr>
                            @foreach ($unitHelper->getUnitTypes() as $unitType)
                                <tr>
                                    <td>{{ $unitHelper->getUnitName($unitType, $dominion->race) }}</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "units.training.{$unitType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @php
                                            $unitsAtHome = (int)array_get($infoOp->data, "units.home.{$unitType}");
                                        @endphp

                                        @if (in_array($unitType, ['spies', 'wizards', 'archmages']))
                                            ???
                                        @elseif ($unitsAtHome !== 0)
                                            ~{{ number_format($unitsAtHome) }}
                                        @else
                                            0
                                        @endif

                                        @if ($amountTraining = array_get($infoOp->data, "units.training.{$unitType}"))
                                            ({{ number_format(array_sum($amountTraining)) }})
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->updated_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                    @endif

                        <div class="pull-right">
                            <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                @csrf
                                <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                <input type="hidden" name="operation" value="barracks_spy">
                                <button type="submit" class="btn btn-sm btn-primary">Barracks Spy</button>
                            </form>
                        </div>
                @endslot
            @endcomponent
        </div>
        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'barracks_spy');
                @endphp

                @slot('title', 'Units returning from battle')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Barracks Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

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
                                @php
                                    $unitType = ('unit' . $slot);
                                @endphp
                                <tr>
                                    <td>{{ $unitHelper->getUnitName($unitType, $dominion->race) }}</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "units.returning.{$unitType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @if ($amountTraining = array_get($infoOp->data, "units.returning.{$unitType}"))
                                            ~{{ number_format(array_sum($amountTraining)) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'survey_dominion');
                @endphp

                @slot('title', 'Constructed Buildings')
                @slot('titleIconClass', 'fa fa-home')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)
                    @slot('titleExtra')
                        <span class="pull-right">Barren Land: {{ number_format(array_get($infoOp->data, 'barren_land')) }}</span>
                    @endslot

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
                                @php
                                    $amount = array_get($infoOp->data, "constructed.{$buildingType}");
                                @endphp
                                <tr>
                                    <td>
                                        {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                        {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                                        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}"></i>
                                    </td>
                                    <td class="text-center">{{ number_format($amount) }}</td>
                                    <td class="text-center">{{ number_format((($amount / $landCalculator->getTotalLand($dominion)) * 100), 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->updated_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="survey_dominion">
                            <button type="submit" class="btn btn-sm btn-primary">Survey Dominion</button>
                        </form>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'survey_dominion');
                @endphp

                @slot('title', 'Incoming building breakdown')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)

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
                                <th>Building Type</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($buildingHelper->getBuildingTypes() as $buildingType)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $buildingType)) }}</td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "constructing.{$buildingType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @if ($amountConstructing = array_get($infoOp->data, "constructing.{$buildingType}"))
                                            {{ number_format(array_sum($amountConstructing)) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'land_spy');
                @endphp

                @slot('title', 'Explored Land')
                @slot('titleIconClass', 'ra ra-honeycomb')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Land Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

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
                                        @if ($landType === $dominion->race->home_land_type)
                                            <small class="text-muted"><i>(home)</i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "explored.{$landType}.amount")) }}</td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "explored.{$landType}.percentage"), 2) }}%</td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "explored.{$landType}.barren")) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    @if ($infoOp !== null)
                        <em>Revealed {{ $infoOp->updated_at }} by {{ $infoOp->sourceDominion->name }}</em>
                        @if ($infoOp->isStale())
                            <span class="label label-warning">Stale</span>
                        @endif
                    @endif

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="land_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Land Spy</button>
                        </form>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'land_spy');
                @endphp

                @slot('title', 'Incoming land breakdown')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Land Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

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
                                <th>Land Type</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    <th class="text-center">{{ $i }}</th>
                                @endfor
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($landHelper->getLandTypes() as $landType)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $dominion->race->home_land_type)
                                            <small class="text-muted"><i>(home)</i></small>
                                        @endif
                                    </td>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $amount = array_get($infoOp->data, "incoming.{$landType}.{$i}", 0);
                                        @endphp
                                        <td class="text-center">
                                            @if ($amount === 0)
                                                -
                                            @else
                                                {{ number_format($amount) }}
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="text-center">
                                        @if ($amountIncoming = array_get($infoOp->data, "incoming.{$landType}"))
                                            {{ number_format(array_sum($amountIncoming)) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-sm-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'clairvoyance');
                @endphp

                @slot('title', 'Town Crier')
                @slot('titleIconClass', 'fa fa-newspaper-o')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Clairvoyance' to reveal information.</p>
                @else
                    <a href="{{ route('dominion.op-center.clairvoyance', $dominion->realm->id) }}">{{ $dominion->realm->name }} (#{{ $dominion->realm->number }})</a>
                    - <em>Revealed <abbr title="{{ $infoOp->updated_at }}">{{ $infoOp->updated_at->diffForHumans() }}</abbr> by {{ $infoOp->sourceDominion->name }}</em>
                    @if ($infoOp->isStale())
                        <span class="label label-warning">Stale</span>
                    @endif
                @endif

                @slot('boxFooter')
                    <div class="pull-right">
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="spell" value="clairvoyance">
                            <button type="submit" class="btn btn-sm btn-primary">Clairvoyance ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'clairvoyance')) }} mana)</button>
                        </form>
                    </div>
                @endslot
            @endcomponent
        </div>

    </div>
@endsection
