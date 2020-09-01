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

                    @php
                        $statusOpData = $infoOp->data;
                        $range = $rangeCalculator->getDominionRange($selectedDominion, $dominion);
                        $rangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion);
                    @endphp

                    @include('partials.dominion.info.status', ['data' => $statusOpData, 'race' => $dominion->race, 'range' => $range, 'rangeClass' => $rangeClass])

                    @if (isset($infoOp->data['clear_sight_accuracy']) && $infoOp->data['clear_sight_accuracy'] != 1)
                        <p class="text-center text-danger" style="margin-bottom: 0.5em;">
                            Illusory magic deceives your wizards! Military information is only {{ $infoOp->data['clear_sight_accuracy'] * 100 }}% accurate.
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

                    @include('partials.dominion.info.improvements-table', ['data' => $infoOp->data])
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

                    @include('partials.dominion.info.military-training-table', ['data' => $infoOp->data, 'isOp' => true, 'race' => $dominion->race ])
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

                    @include('partials.dominion.info.military-returning-table', ['data' => $infoOp->data, 'isOp' => true, 'race' => $dominion->race ])
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

                    @include('partials.dominion.info.construction-constructed-table', ['data' => $infoOp->data])
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

                    @include('partials.dominion.info.construction-constructing-table', ['data' => $infoOp->data])
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

                    @include('partials.dominion.info.land-table', ['data' => $infoOp->data, 'race' => $dominion->race])
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

                    @include('partials.dominion.info.land-incoming-table', ['data' => $infoOp->data, 'race' => $dominion->race])
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

                    @include('partials.dominion.info.techs-table', ['data' => $infoOp->data['techs']])
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
