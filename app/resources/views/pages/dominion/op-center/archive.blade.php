@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @component('partials.dominion.op-center.box')
                @slot('title', ('Archived Ops (' . $dominion->name . ')'))
                @slot('titleIconClass', 'fa fa-book')

                <p>This page contains the data that your realmies have gathered about dominion <b>{{ $dominion->name }}</b> from realm <a href="{{ route('dominion.realm', [$dominion->realm->number]) }}">{{ $dominion->realm->name }} (#{{ $dominion->realm->number }})</a>.</p>

                <p>
                    @if (!$infoOpArchive->count())
                        No recent data available.
                    @endif
                </p>

                @slot('boxFooter')
                    <div class="pull-right">
                        <a href="{{ route('dominion.op-center.show', $dominion) }}" class="btn btn-sm btn-primary">Back to Overview</a>
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
                <p>Sections marked as <span class="label label-warning">stale</span> contain data from the previous hour (or earlier) and should be considered inaccurate. Sections marked as <span class="label label-danger">invalid</span> are more than 12 hours old. Recast your info ops before performing any offensive operations during this hour.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($infoOpArchive as $infoOp)
            @if ($infoOp->type == 'clear_sight')
                <div class="col-sm-12 col-md-9">
                    @component('partials.dominion.op-center.box')
                        @slot('title', ('Status Screen (' . $dominion->name . ')'))
                        @slot('titleIconClass', 'fa fa-bar-chart')

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

                        @slot('boxFooter')
                            @if ($infoOp !== null)
                                <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                @if ($infoOp->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($infoOp->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                            @endif
                        @endslot
                    @endcomponent
                </div>
            @endif

            @if ($infoOp->type == 'revelation')
                <div class="col-sm-12 col-md-6">
                    @component('partials.dominion.op-center.box')
                        @slot('title', 'Active Spells')
                        @slot('titleIconClass', 'ra ra-fairy-wand')

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

                        @slot('boxFooter')
                            @if ($infoOp !== null)
                                <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                @if ($infoOp->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($infoOp->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                            @endif
                        @endslot
                    @endcomponent
                </div>
            @endif

            @if ($infoOp->type == 'castle_spy')
                <div class="col-sm-12 col-md-6">
                    @component('partials.dominion.op-center.box')
                        @slot('title', 'Improvements')
                        @slot('titleIconClass', 'fa fa-arrow-up')

                        @slot('noPadding', true)

                        @include('partials.dominion.info.improvements-table', ['data' => $infoOp->data])

                        @slot('boxFooter')
                            @if ($infoOp !== null)
                                <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                @if ($infoOp->isInvalid())
                                    <span class="label label-danger">Invalid</span>
                                @elseif ($infoOp->isStale())
                                    <span class="label label-warning">Stale</span>
                                @endif
                                <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                            @endif
                        @endslot
                    @endcomponent
                </div>
            @endif

            @if ($infoOp->type == 'barracks_spy')
                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
                                @slot('title', 'Units in training and home')
                                @slot('titleIconClass', 'ra ra-sword')

                                @slot('noPadding', true)

                                @include('partials.dominion.info.military-training-table', ['data' => $infoOp->data, 'isOp' => true, 'race' => $dominion->race ])

                                @slot('boxFooter')
                                    @if ($infoOp !== null)
                                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                        @if ($infoOp->isInvalid())
                                            <span class="label label-danger">Invalid</span>
                                        @elseif ($infoOp->isStale())
                                            <span class="label label-warning">Stale</span>
                                        @endif
                                        <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                                    @endif
                                @endslot
                            @endcomponent
                        </div>

                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
                                @slot('title', 'Units returning from battle')
                                @slot('titleIconClass', 'fa fa-clock-o')

                                @slot('noPadding', true)

                                @include('partials.dominion.info.military-returning-table', ['data' => $infoOp->data, 'isOp' => true, 'race' => $dominion->race ])
                            @endcomponent
                        </div>
                    </div>
                </div>
            @endif

            @if ($infoOp->type == 'survey_dominion')
                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
                                @slot('title', 'Constructed Buildings')
                                @slot('titleIconClass', 'fa fa-home')

                                @slot('noPadding', true)
                                @slot('titleExtra')
                                    <span class="pull-right">Barren Land: {{ number_format(array_get($infoOp->data, 'barren_land')) }}</span>
                                @endslot

                                @include('partials.dominion.info.construction-constructed-table', ['data' => $infoOp->data])

                                @slot('boxFooter')
                                    @if ($infoOp !== null)
                                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                        @if ($infoOp->isInvalid())
                                            <span class="label label-danger">Invalid</span>
                                        @elseif ($infoOp->isStale())
                                            <span class="label label-warning">Stale</span>
                                        @endif
                                        <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                                    @endif
                                @endslot
                            @endcomponent
                        </div>

                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
                                @slot('title', 'Incoming building breakdown')
                                @slot('titleIconClass', 'fa fa-clock-o')

                                @slot('noPadding', true)

                                @include('partials.dominion.info.construction-constructing-table', ['data' => $infoOp->data])
                            @endcomponent
                        </div>
                    </div>
                </div>
            @endif

            @if ($infoOp->type == 'land_spy')
                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
                                @slot('title', 'Explored Land')
                                @slot('titleIconClass', 'ra ra-honeycomb')

                                @slot('noPadding', true)

                                @include('partials.dominion.info.land-table', ['data' => $infoOp->data, 'race' => $dominion->race])

                                @slot('boxFooter')
                                    @if ($infoOp !== null)
                                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                        @if ($infoOp->isInvalid())
                                            <span class="label label-danger">Invalid</span>
                                        @elseif ($infoOp->isStale())
                                            <span class="label label-warning">Stale</span>
                                        @endif
                                        <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                                    @endif
                                @endslot
                            @endcomponent
                        </div>

                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
                                @slot('title', 'Incoming land breakdown')
                                @slot('titleIconClass', 'fa fa-clock-o')

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
                            @endcomponent
                        </div>
                    </div>
                </div>
            @endif

            @if ($infoOp->type == 'vision')
                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            @component('partials.dominion.op-center.box')
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
                                    @if ($infoOp !== null)
                                        <em>Revealed {{ $infoOp->created_at }} by {{ $infoOp->sourceDominion->name }}</em>
                                        @if ($infoOp->isInvalid())
                                            <span class="label label-danger">Invalid</span>
                                        @elseif ($infoOp->isStale())
                                            <span class="label label-warning">Stale</span>
                                        @endif
                                        <br><span class="label label-default">Day {{ $selectedDominion->round->start_date->subDays(1)->diffInDays($infoOp->created_at) }}</span>
                                    @endif

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
                                @slot('title', 'Tech Bonuses')
                                @slot('titleIconClass', 'ra ra-fizzing-flask')

                                @if ($infoOp === null)
                                    <p>No recent data available.</p>
                                    <p>Cast magic spell 'Vision' to reveal information.</p>
                                @else
                                    @slot('noPadding', true)

                                    @include('partials.dominion.info.techs-combined', ['data' => $infoOp->data['techs']])
                                @endif
                            @endcomponent
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="text-center">
                {{ $infoOpArchive->links() }}
            </div>
        </div>
    </div>
@endsection
