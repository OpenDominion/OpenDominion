@extends('layouts.master')

@section('page-header', 'Op Center')

@php

    use Carbon\Carbon;
    if (!isset($inRealm)) {
        $inRealm = false;
        $targetDominion = null;
    }

    $latestClearSight = $latestInfoOps->firstWhere('type', 'clear_sight');
    $latestRevelation = $latestInfoOps->firstWhere('type', 'revelation');
    $latestCastle = $latestInfoOps->firstWhere('type', 'castle_spy');
    $latestBarracks = $latestInfoOps->firstWhere('type', 'barracks_spy');
    $latestSurvey = $latestInfoOps->firstWhere('type', 'survey_dominion');
    $latestLand = $latestInfoOps->firstWhere('type', 'land_spy');
    $latestVision = $latestInfoOps->firstWhere('type', 'vision');
    $latestDisclosure = $latestInfoOps->firstWhere('type', 'disclosure');

    $infoOps = [
        'status' => null,
        'revelation' => null,
        'castle' => null,
        'barracks' => null,
        'survey' => null,
        'land' => null,
        'vision' => null,
        'disclosure' => null
    ];

    $now = Carbon::now();

    if ($latestClearSight != null) {
        $infoOps['status'] = $latestClearSight->data;
        $infoOps['status']['race_name'] = $dominion->race->name;
        $infoOps['status']['created_at'] = isset($latestClearSight->created_at) ? $latestClearSight->created_at : $now;
        $infoOps['status']['realm'] = $dominion->realm->number;
        $infoOps['status']['name'] = $dominion->name;
        unset($infoOps['status']['race_id']);
    }

    if ($latestRevelation != null) {
        $infoOps['revelation'] = [];
        $infoOps['revelation']['spells'] = $latestRevelation->data;
        $infoOps['revelation']['created_at'] = isset($latestRevelation->created_at) ? $latestRevelation->created_at : $now;
    }

    if ($latestCastle != null) {
        $infoOps['castle'] = $latestCastle->data;
        $infoOps['castle']['created_at'] = isset($latestCastle->created_at) ? $latestCastle->created_at : $now;
    }

    if ($latestBarracks != null) {
        $infoOps['barracks'] = $latestBarracks->data;
        $infoOps['barracks']['created_at'] = isset($latestBarracks->created_at) ? $latestBarracks->created_at : $now;
    }

    if ($latestSurvey != null) {
        $infoOps['survey'] = $latestSurvey->data;
        $infoOps['survey']['created_at'] = isset($latestSurvey->created_at) ? $latestSurvey->created_at : $now;
    }

    if ($latestLand != null) {
        $infoOps['land'] = $latestLand->data;
        $infoOps['land']['created_at'] = isset($latestLand->created_at) ? $latestLand->created_at : $now;
    }

    if ($latestVision != null) {
        $infoOps['vision'] = $latestVision->data;
        $infoOps['vision']['created_at'] = isset($latestVision->created_at) ? $latestVision->created_at : $now;
    }

    if ($latestDisclosure != null) {
        $infoOps['disclosure'] = $latestDisclosure->data;
        $infoOps['disclosure']['created_at'] = isset($latestDisclosure->created_at) ? $latestDisclosure->created_at : $now;
    }

    $infoSpells = $spellHelper->getSpells($dominion->race, 'info');

@endphp

@section('content')
    @if ($inRealm)
        @include('partials.dominion.advisor-selector')
    @endif
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @component('partials.dominion.op-center.box')
                @slot('title', ('Status Screen - ' . $dominion->name . ' (#' . $dominion->realm->number . ')'))
                @slot('titleIconClass', 'fa fa-bar-chart')
                @slot('opData', $infoOps['status'])
                @slot('opKey', 'status')

                @if ($latestClearSight === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Clear Sight' to reveal information.</p>
                @else
                    @slot('tableResponsive', false)
                    @slot('noPadding', true)

                    @php
                        $statusOpData = $latestClearSight->data;
                        $range = $rangeCalculator->getDominionRange($selectedDominion, $dominion);
                        $rangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion);
                    @endphp

                    @include('partials.dominion.info.status', ['data' => $statusOpData, 'race' => $dominion->race, 'range' => $range, 'rangeClass' => $rangeClass])

                    @if (isset($latestClearSight->data['clear_sight_accuracy']) && $latestClearSight->data['clear_sight_accuracy'] != 1)
                        <p class="text-center text-danger" style="margin-bottom: 0.5em;">
                            Illusory magic deceives your wizards! Military information is only {{ $latestClearSight->data['clear_sight_accuracy'] * 100 }}% accurate.
                        </p>
                    @endif

                    @php
                        $recentlyInvadedCount = (isset($latestClearSight->data['recently_invaded_count']) ? (int)$latestClearSight->data['recently_invaded_count'] : 0);
                    @endphp

                    @if ($recentlyInvadedCount > 0)
                        <p class="text-center" style="margin-bottom: 0.5em;" data-toggle="tooltip" title="Defensive casualties reduced by {{ 20 * $recentlyInvadedCount }}%.">
                            This dominion has been invaded <strong>{{ $recentlyInvadedCount }}</strong> time(s) in the last 24 hours.
                        </p>
                    @endif
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestClearSight !== null)
                                <em>Revealed {{ $latestClearSight->created_at }} by {{ $latestClearSight->sourceDominion->name }}</em>
                                @if ($latestClearSight->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestClearSight->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestClearSight->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestClearSight->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'clear_sight'
                                ])
                                <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                <input type="hidden" name="spell" value="clear_sight">
                                <button type="submit" class="btn btn-sm btn-primary">Clear Sight ({{ number_format($spellCalculator->getManaCost($selectedDominion, $infoSpells->get('clear_sight'))) }} mana)</button>
                            </form>
                        </div>
                        <div class="clearfix"></div>

                        <div class="text-center">
                            <a href="{{ route('dominion.op-center.archive', [$dominion, 'clear_sight']) }}">View Archives</a>
                        </div>
                    @endslot
                @endif
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Information</h3>
                        </div>
                        <div class="box-body">
                            @if (!$inRealm)
                                <p>This page contains the data that your realmies have gathered about dominion <b>{{ $dominion->name }}</b> from realm <a href="{{ route('dominion.realm', [$dominion->realm->number]) }}">{{ $dominion->realm->name }} (#{{ $dominion->realm->number }})</a>.</p>

                                <p>Sections marked as <span class="label label-warning">stale</span> contain data from the previous hour (or earlier) and should be considered inaccurate. Sections marked as <span class="label label-danger">invalid</span> are more than 12 hours old.</p>

                                <p><b>Recast your info ops before performing any offensive operations during this hour.</b></p>
                            @endif

                            <p>You can automatically load data into the calculator or copy all data as JSON.</p>

                            <div>
                                <div class="pull-left">
                                    <a href="{{ route('dominion.calculations.military') }}?dominion={{ $dominion->id }}" class="btn btn-primary">
                                        <i class="fa fa-calculator"></i> Calculate
                                    </a>
                                </div>
                                <div class="pull-right">
                                    <a class="btn btn-primary" onclick="copyJson('ops_json')">
                                        <i class="fa fa-copy"></i> Copy ops
                                    </a>
                                    <textarea class="hidden" name="ops_json" id="ops_json">{{ json_encode($spellHelper->obfuscateInfoOps($infoOps), JSON_PRETTY_PRINT) }}</textarea>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </div>

                        <div class="box-footer">
                            <table class="table table-condensed" style="margin-bottom: 10px;">
                                <thead>
                                    <tr>
                                        <th>{{ $dominion->race->name }} Perks</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dominion->race->perks as $perk)
                                        @php
                                            $perkDescription = $raceHelper->getPerkDescriptionHtmlWithValue($perk);
                                        @endphp
                                        <tr>
                                            <td>
                                                {!! $perkDescription['description'] !!}
                                            </td>
                                            <td class="text-center">
                                                {!! $perkDescription['value']  !!}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @if (!$inRealm)
                                <div class="text-center">
                                    <a href="{{ route('dominion.invade') }}?dominion={{ $dominion->id }}"
                                        class="btn btn-danger" style="font-size: 20px; padding: 3px 6px 0px;"
                                        title="Invade" data-toggle="tooltip">
                                        <i class="ra ra-crossed-swords"></i>
                                    </a>
                                    <a href="{{ route('dominion.magic') }}?dominion={{ $dominion->id }}"
                                        class="btn btn-warning" style="font-size: 20px; padding: 3px 6px 0px;"
                                        title="Magic" data-toggle="tooltip">
                                        <i class="ra ra-fairy-wand"></i>
                                    </a>
                                    <a href="{{ route('dominion.espionage') }}?dominion={{ $dominion->id }}"
                                        class="btn btn-warning" style="font-size: 20px; padding: 3px 6px 0px;"
                                        title="Espionage" data-toggle="tooltip">
                                        <i class="fa fa-user-secret"></i>
                                    </a>
                                    @if ($selectedDominion->isMonarch() || $selectedDominion->isSpymaster())
                                        @if (in_array($dominion->id, $selectedDominion->realm->getSetting('observeDominionIds') ?? []))
                                            <a href="{{ route('dominion.bounty-board.observe', $dominion->id) }}"
                                                class="btn btn-danger" style="font-size: 20px; padding: 3px 6px 0px;"
                                                title="Cancel Observation" data-toggle="tooltip">
                                                <i class="fa fa-eye-slash"></i>
                                            </a>
                                        @else
                                            <a href="{{ route('dominion.bounty-board.observe', $dominion->id) }}"
                                              class="btn btn-info" style="font-size: 20px; padding: 3px 6px 0px;"
                                              title="Mark for Observation" data-toggle="tooltip">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Active Spells')
                @slot('titleIconClass', 'ra ra-fairy-wand')
                @slot('opData', $infoOps['revelation'])
                @slot('opKey', 'revelation')

                @if ($latestRevelation === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Revelation' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="20%">
                            <col>
                            <col width="10%">
                            <col width="25%">
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
                            @foreach ($latestRevelation->data as $activeSpell)
                                @php
                                    $spell = $spellHelper->getSpellByKey($activeSpell['spell']);
                                @endphp
                                <tr>
                                    <td>
                                        {{ $spell->name }}
                                    </td>
                                    <td>
                                        <div data-toggle="tooltip" data-placement="top" title="{{ $spellHelper->getSpellDescription($spell) }}">
                                            {{ $spellHelper->getSpellDescription($spell) }}
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $activeSpell['duration'] }}</td>
                                    <td class="text-center">
                                        @php
                                            if (!isset($activeSpell['cast_by_dominion_name'])) {
                                                if ($activeSpell['cast_by_dominion_id'] == $dominion->id) {
                                                    $activeSpell['cast_by_dominion_name'] = $dominion->name;
                                                    $activeSpell['cast_by_dominion_realm_number'] = $dominion->realm->number;
                                                } else {
                                                    $castByDominion = OpenDominion\Models\Dominion::with('realm')->findOrFail($activeSpell['cast_by_dominion_id']);
                                                    $activeSpell['cast_by_dominion_name'] = $castByDominion->name;
                                                    $activeSpell['cast_by_dominion_realm_number'] = $castByDominion->realm->number;
                                                }
                                            }
                                        @endphp
                                        @if ($activeSpell['cast_by_dominion_id'] == $dominion->id || $activeSpell['cast_by_dominion_realm_number'] == $selectedDominion->realm->number || ($dominion->realm_id == $selectedDominion->realm_id && $dominion->getSpellPerkValue('surreal_perception')))
                                            <a href="{{ route('dominion.realm', $activeSpell['cast_by_dominion_realm_number']) }}">{{ $activeSpell['cast_by_dominion_name'] }} (#{{ $activeSpell['cast_by_dominion_realm_number'] }})</a>
                                        @else
                                            <em>Unknown</em>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestRevelation !== null)
                                <em>Revealed {{ $latestRevelation->created_at }} by {{ $latestRevelation->sourceDominion->name }}</em>
                                @if ($latestRevelation->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestRevelation->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestRevelation->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestRevelation->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'revelation'
                                ])
                                <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                <input type="hidden" name="spell" value="revelation">
                                <button type="submit" class="btn btn-sm btn-primary">Revelation ({{ number_format($spellCalculator->getManaCost($selectedDominion, $infoSpells->get('revelation'))) }} mana)</button>
                            </form>
                        </div>
                        <div class="clearfix"></div>

                        <div class="text-center">
                            <a href="{{ route('dominion.op-center.archive', [$dominion, 'revelation']) }}">View Archives</a>
                        </div>
                    @endslot
                @endif
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Improvements')
                @slot('titleIconClass', 'fa fa-arrow-up')
                @slot('opData', $infoOps['castle'])
                @slot('opKey', 'castle')

                @if ($latestCastle === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Castle Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.improvements-table', ['data' => $latestCastle->data])
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestCastle !== null)
                                <em>Revealed {{ $latestCastle->created_at }} by {{ $latestCastle->sourceDominion->name }}</em>
                                @if ($latestCastle->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestCastle->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestCastle->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestCastle->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'castle_spy'
                                ])
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
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Units in training and home')
                @slot('titleIconClass', 'ra ra-sword')
                @slot('opData', $infoOps['barracks'])
                @slot('opKey', 'barracks')

                @if ($latestBarracks === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Barracks Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.military-training-table', ['data' => $latestBarracks->data, 'isOp' => true, 'race' => $dominion->race ])
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestBarracks !== null)
                                <em>Revealed {{ $latestBarracks->created_at }} by {{ $latestBarracks->sourceDominion->name }}</em>
                                @if ($latestBarracks->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestBarracks->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestBarracks->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestBarracks->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'barracks_spy'
                                ])
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
                @endif
            @endcomponent
        </div>
        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Units returning from battle')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($latestBarracks === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Barracks Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.military-returning-table', ['data' => $latestBarracks->data, 'isOp' => true, 'race' => $dominion->race ])
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Constructed Buildings')
                @slot('titleIconClass', 'fa fa-home')
                @slot('opData', $infoOps['survey'])
                @slot('opKey', 'survey')

                @if ($latestSurvey === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)
                    @slot('titleExtra')
                        <span class="pull-right">Barren Land: <strong>{{ number_format(array_get($latestSurvey->data, 'barren_land')) }}</strong> <small>({{ number_format((array_get($latestSurvey->data, 'barren_land') / array_get($latestSurvey->data, 'total_land', 250)) * 100, 2) }}%)</small></span>
                    @endslot

                    @include('partials.dominion.info.construction-constructed-table', ['data' => $latestSurvey->data])
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestSurvey !== null)
                                <em>Revealed {{ $latestSurvey->created_at }} by {{ $latestSurvey->sourceDominion->name }}</em>
                                @if ($latestSurvey->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestSurvey->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestSurvey->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestSurvey->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'survey_dominion'
                                ])
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
                @endif
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Incoming building breakdown')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($latestSurvey === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Survey Dominion' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.construction-constructing-table', ['data' => $latestSurvey->data])
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Explored Land')
                @slot('titleIconClass', 'ra ra-honeycomb')
                @slot('opData', $infoOps['land'])
                @slot('opKey', 'land')

                @if ($latestLand === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Land Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.land-table', ['data' => $latestLand->data, 'race' => $dominion->race])
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestLand !== null)
                                <em>Revealed {{ $latestLand->created_at }} by {{ $latestLand->sourceDominion->name }}</em>
                                @if ($latestLand->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestLand->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestLand->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestLand->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'land_spy'
                                ])
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
                @endif
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Incoming land breakdown')
                @slot('titleIconClass', 'fa fa-clock-o')

                @if ($latestLand === null)
                    <p>No recent data available.</p>
                    <p>Perform espionage operation 'Land Spy' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.land-incoming-table', ['data' => $latestLand->data, 'race' => $dominion->race])
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')

                @slot('title', 'Technological Advancements')
                @slot('titleIconClass', 'fa fa-flask')
                @slot('opData', $infoOps['vision'])
                @slot('opKey', 'vision')

                @if ($latestVision === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Vision' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.techs-table', ['data' => $latestVision->data['techs']])
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestVision !== null)
                                <em>Revealed {{ $latestVision->created_at }} by {{ $latestVision->sourceDominion->name }}</em>
                                @if ($latestVision->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestVision->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestVision->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestVision->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'vision'
                                ])
                                <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                <input type="hidden" name="spell" value="vision">
                                <button type="submit" class="btn btn-sm btn-primary">Vision ({{ number_format($spellCalculator->getManaCost($selectedDominion, $infoSpells->get('vision'))) }} mana)</button>
                            </form>
                        </div>
                        <div class="clearfix"></div>

                        <div class="text-center">
                            <a href="{{ route('dominion.op-center.archive', [$dominion, 'vision']) }}">View Archives</a>
                        </div>
                    @endslot
                @endif
            @endcomponent
        </div>

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')
                @slot('title', 'Tech Bonuses')
                @slot('titleIconClass', 'ra ra-fizzing-flask')

                @if ($latestVision === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Vision' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    @include('partials.dominion.info.techs-combined', ['data' => $latestVision->data['techs']])
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-6">
            @component('partials.dominion.op-center.box')

                @slot('title', 'Heroes')
                @slot('titleIconClass', 'ra ra-knight-helmet')
                @slot('opData', $infoOps['disclosure'])
                @slot('opKey', 'disclosure')

                @if ($latestDisclosure === null)
                    <p>No recent data available.</p>
                    <p>Cast magic spell 'Disclosure' to reveal information.</p>
                @else
                    @slot('noPadding', true)

                    <table class="table">
                        <colgroup>
                            <col width="25%">
                            <col width="75%">
                        </colgroup>
                        <tbody>
                            @foreach ($latestDisclosure->data as $hero)
                                <tr>
                                    <td class="text-right">Name</td>
                                    <td class="text-left">{{ $hero['name'] }}</td>
                                </tr>
                                <tr>
                                    <td class="text-right">Class</td>
                                    <td class="text-left">{{ ucwords($hero['class']) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-right">Level</td>
                                    <td class="text-left">{{ $hero['level'] }}</td>
                                </tr>
                                <tr>
                                    <td class="text-right">Experience</td>
                                    <td class="text-left">{{ $hero['experience'] }} / {{ $hero['next_level_xp'] }}</td>
                                </tr>
                                <tr>
                                    <td class="text-right">{{ ucwords(str_replace('_', ' ', $heroHelper->getPassivePerkType($hero['class']))) }}</td>
                                    <td class="text-left">{{ number_format($hero['bonus'], 4) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if (!$inRealm)
                    @slot('boxFooter')
                        <div class="pull-left">
                            @if ($latestDisclosure !== null)
                                <em>Revealed {{ $latestDisclosure->created_at }} by {{ $latestDisclosure->sourceDominion->name }}</em>
                                @if ($latestDisclosure->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($latestDisclosure->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br>
                                <span class="label label-default">Day {{ $selectedDominion->round->daysInRound($latestDisclosure->created_at) }}</span>
                                <span class="label label-default">Hour {{ $selectedDominion->round->hoursInDay($latestDisclosure->created_at) }}</span>
                            @endif
                        </div>

                        <div class="pull-right">
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf
                                @include('partials.dominion.bounty.show-item', [
                                    'bounties' => $bounties,
                                    'opType' => 'disclosure'
                                ])
                                <input type="hidden" name="target_dominion" value="{{ $dominion->id }}">
                                <input type="hidden" name="spell" value="disclosure">
                                <button type="submit" class="btn btn-sm btn-primary">Disclosure ({{ number_format($spellCalculator->getManaCost($selectedDominion, $infoSpells->get('disclosure'))) }} mana)</button>
                            </form>
                        </div>
                        <div class="clearfix"></div>

                        <div class="text-center">
                            <a href="{{ route('dominion.op-center.archive', [$dominion, 'disclosure']) }}">View Archives</a>
                        </div>
                    @endslot
                @endif
            @endcomponent
        </div>

    </div>
    <div class="row">

        <div class="col-sm-12 col-md-12">
            <div class="box box-primary">
                <div class="box-header" id="recent-invasions">
                    <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Recent Invasions</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table">
                        <tbody>
                            @foreach($latestInvasionEvents as $invasionEvent)
                                @php
                                    $sourceRange = round($rangeCalculator->getDominionRange($selectedDominion, $invasionEvent->source), 2);
                                    $sourceRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $invasionEvent->source);
                                    $sourceRaceName = $invasionEvent->source->race->name;
                                    $sourceToolTipHtml = "$sourceRaceName (<span class=\"$sourceRangeClass\">$sourceRange%</span>)";

                                    $targetRange = round($rangeCalculator->getDominionRange($selectedDominion, $invasionEvent->target), 2);
                                    $targetRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $invasionEvent->target);
                                    $targetRaceName = $invasionEvent->target->race->name;
                                    $targetToolTipHtml = "$targetRaceName (<span class=\"$targetRangeClass\">$targetRange%</span>)";

                                    $sourceTextColor = 'text-light-blue';
                                    if ($invasionEvent->source->realm_id == $selectedDominion->realm_id) {
                                        $sourceTextColor = 'text-green';
                                    } elseif ($invasionEvent->target->realm_id == $selectedDominion->realm_id) {
                                        $sourceTextColor = 'text-red';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <span>{{ $invasionEvent->created_at }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('dominion.op-center.show', [$invasionEvent->source->id]) }}"><span class="{{ $sourceTextColor }}" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $invasionEvent->source->name }}</span></a>
                                        <a href="{{ route('dominion.realm', [$invasionEvent->source->realm->number]) }}">(#{{ $invasionEvent->source->realm->number }})</a>
                                        invaded
                                        <a href="{{ route('dominion.op-center.show', [$invasionEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $invasionEvent->target->name }}</span></a>
                                        <a href="{{ route('dominion.realm', [$invasionEvent->target->realm->number]) }}">(#{{ $invasionEvent->target->realm->number }})</a>
                                        @if ($invasionEvent->data['result']['success'])
                                            and captured
                                            <span class="text-orange text-bold">{{ number_format(array_sum($invasionEvent->data['attacker']['landConquered'])) }}</span> land.
                                        @else
                                            but failed to conquer any land.
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invasionEvent->source->realm_id == $selectedDominion->realm->id || $invasionEvent->target->realm_id == $selectedDominion->realm->id)
                                            <a href="{{ route('dominion.event', [$invasionEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
                                        @endif
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

@push('inline-scripts')
    <script type="text/javascript">
        function copyJson(elementId) {
            const input = document.getElementById(elementId);
            input.className = '';
            input.select();
            input.setSelectionRange(0, 99999);

            document.execCommand("copy");
            input.className = 'hidden';
        }
    </script>
@endpush
