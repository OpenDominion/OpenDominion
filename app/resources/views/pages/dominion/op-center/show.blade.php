@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'clear_sight');
                @endphp

                @slot('title', ('Status Screen (' . $dominion->name . ')'))
                @slot('titleIconClass', 'fa fa-bar-chart')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Clear Sight' to reveal information.</p>
                @else
                    @slot('tableResponsive', false)
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
                                        <td>{{ $dominion->race->name }}</td>
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
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("prestige") }}">
                                                Prestige:
                                            </span>
                                        </td>
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
                                        <td>Research Points:</td>
                                        <td>{{ number_format($infoOp->data['resource_tech']) }}</td>
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
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("morale") }}">
                                                Morale:
                                            </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['morale']) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $dominion->race, true) }}">
                                                Draftees:
                                            </span>
                                        </td>
                                        <td>{{ number_format($infoOp->data['military_draftees']) }}</td>
                                    </tr>
                                    @foreach ($unitHelper->getUnitTypes() as $unitType)
                                        @php
                                            $unit = $dominion->race->units->filter(function ($unit) use ($unitType) {
                                                return ($unit->slot == (int)str_replace('unit', '', $unitType));
                                            })->first();
                                        @endphp
                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race, true) }}">
                                                    {{ $unitHelper->getUnitName($unitType, $dominion->race) }}:
                                                </span>
                                            </td>
                                            @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
                                                <td>
                                                    {{ number_format($infoOp->data["military_unit$unit->slot"]) }}
                                                </td>
                                            @else
                                                <td>
                                                    ???
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if (isset($infoOp->data['clear_sight_accuracy']) && $infoOp->data['clear_sight_accuracy'] != 1)
                        <p class="text-center text-danger" style="margin-bottom: 0.5em;">
                            Military information is only {{ $infoOp->data['clear_sight_accuracy'] * 100 }}% accurate due to magical interference.
                        </p>
                    @endif

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
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="spell" value="clear_sight">
                            <button type="submit" class="btn btn-sm btn-primary">Clear Sight ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'clear_sight')) }} mana)</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'clear_sight']) }}">View Archives</a>
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
                    <p>This page contains the data that your realmies have gathered about dominion <b>{{ $dominion->name }}</b> from realm <a href="{{ route('dominion.realm', [$dominion->realm->number]) }}">{{ $dominion->realm->name }} (#{{ $dominion->realm->number }})</a>.</p>

                    <p>Sections marked as <span class="label label-warning">stale</span> contain data from the previous hour (or earlier) and should be considered inaccurate. Sections marked as <span class="label label-danger">invalid</span> are more than 12 hours old.</p>

                    <p><b>Recast your info ops before performing any offensive operations during this hour.</b></p>

                    <p>You can automatically load the most recent ops into the calculator.</p>

                    <p>
                        <a href="{{ route('dominion.calculations') }}?dominion={{ $dominion->id }}" class="btn btn-primary">
                            <i class="fa fa-calculator"></i> Calculate
                        </a>
                    </p>
                </div>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'revelation');
                @endphp

                @slot('title', 'Active Spells')
                @slot('titleIconClass', 'ra ra-fairy-wand')

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
                            <col width="200">
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
                                    $spellInfo = $spellHelper->getSpellInfo($spell['spell']);
                                    $castByDominion = OpenDominion\Models\Dominion::with('realm')->findOrFail($spell['cast_by_dominion_id']);
                                @endphp
                                <tr>
                                    <td>{{ $spellInfo['name'] }}</td>
                                    <td>{{ $spellInfo['description'] }}</td>
                                    <td class="text-center">{{ $spell['duration'] }}</td>
                                    <td class="text-center">
                                        @if ($castByDominion->id == $dominion->id || $castByDominion->realm_id == $selectedDominion->realm_id)
                                            <a href="{{ route('dominion.realm', $castByDominion->realm->number) }}">{{ $castByDominion->name }} (#{{ $castByDominion->realm->number }})</a>
                                        @else
                                            <em>Unknown</em>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="spell" value="revelation">
                            <button type="submit" class="btn btn-sm btn-primary">Revelation ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'revelation')) }} mana)</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'revelation']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'castle_spy');
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
                                        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $improvementHelper->getImprovementHelpString($improvementType) }}"></i>
                                    </td>
                                    <td>
                                        {{ sprintf(
                                            $improvementHelper->getImprovementRatingString($improvementType),
                                            number_format((array_get($infoOp->data, "{$improvementType}.rating") * 100), 2),
                                            number_format((array_get($infoOp->data, "{$improvementType}.rating") * 100 * 2), 2)
                                        ) }}
                                    </td>
                                    <td class="text-center">{{ number_format(array_get($infoOp->data, "{$improvementType}.points")) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="castle_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Castle Spy</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'castle_spy']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'barracks_spy');
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
                                <td>
                                    <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $dominion->race, true) }}">
                                        Draftees:
                                    </span>
                                </td>
                                <td colspan="12">&nbsp;</td>
                                <td class="text-center">
                                    ~{{ number_format(array_get($infoOp->data, 'units.home.draftees', 0)) }}
                                </td>
                            </tr>
                            @foreach ($unitHelper->getUnitTypes() as $unitType)
                                <tr>
                                    <td>
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race, true) }}">
                                            {{ $unitHelper->getUnitName($unitType, $dominion->race) }}:
                                        </span>
                                    </td>
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
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="barracks_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Barracks Spy</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'barracks_spy']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>
        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'barracks_spy');
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
                    $infoOp = $latestInfoOps->firstWhere('type', 'survey_dominion');
                @endphp

                @slot('title', 'Constructed Buildings')
                @slot('titleIconClass', 'fa fa-home')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)
                    @slot('titleExtra')
                        <span class="pull-right">Barren Land: <strong>{{ number_format(array_get($infoOp->data, 'barren_land')) }}</strong></span>
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
                                        <span data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}">
                                            {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                        </span>
                                        {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                                    </td>
                                    <td class="text-center">{{ number_format($amount) }}</td>
                                    <td class="text-center">{{ number_format((($amount / array_get($infoOp->data, "total_land", $landCalculator->getTotalLand($dominion))) * 100), 2) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="survey_dominion">
                            <button type="submit" class="btn btn-sm btn-primary">Survey Dominion</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'survey_dominion']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'survey_dominion');
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
                    $infoOp = $latestInfoOps->firstWhere('type', 'land_spy');
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
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="operation" value="land_spy">
                            <button type="submit" class="btn btn-sm btn-primary">Land Spy</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'land_spy']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'land_spy');
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

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @php
                    $infoOp = $latestInfoOps->firstWhere('type', 'vision');
                @endphp

                @slot('title', 'Technological Advancements')
                @slot('titleIconClass', 'fa fa-flask')

                @if ($infoOp === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Vision' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="150">
                            <col>
                            <col width="100">
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Tech</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($infoOp->data['techs'] as $techKey => $techName)
                                @php
                                    $techDescription = $techHelper->getTechDescription(OpenDominion\Models\Tech::where('key', $techKey)->firstOrFail());
                                @endphp
                                <tr>
                                    <td>{{ $techName }}</td>
                                    <td>{{ $techDescription }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @slot('boxFooter')
                    <div class="pull-left">
                        @if ($infoOp !== null)
                            <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                            @if ($infoOp->isInvalid())
                                <span class="label label-danger">Invalid</span>
                            @elseif ($infoOp->isStale())
                                <span class="label label-warning">Stale</span>
                            @endif
                            <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                        @endif
                    </div>

                    <div class="pull-right">
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf
                            <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                            <input type="hidden" name="spell" value="vision">
                            <button type="submit" class="btn btn-sm btn-primary">Vision ({{ number_format($spellCalculator->getManaCost($selectedDominion, 'vision')) }} mana)</button>
                        </form>
                    </div>
                    <div class="clearfix"></div>

                    <div class="text-center">
                        <a href="{{ route('dominion.op-center.archive', [$dominion, 'vision']) }}">View Archives</a>
                    </div>
                @endslot
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Heroes')
                @slot('titleIconClass', 'ra ra-knight-helmet')
                <p>Not yet implemented.</p>
            @endcomponent
        </div>
    </div>
@endsection
