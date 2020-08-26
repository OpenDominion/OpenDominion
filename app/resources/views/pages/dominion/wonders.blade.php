@extends('layouts.master')

@section('page-header', 'Wonders of the World')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-pyramids ra-lg"></i> Wonders of the World</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                            <col>
                            <col width="10%">
                            <col width="20%">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Realm</th>
                                <th>Power</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($wonders->sortBy('wonder.name') as $wonder)
                                <tr>
                                    <td>
                                        {{ $wonder->wonder->name }}
                                    </td>
                                    <td>
                                        @if ($wonder->realm)
                                            #{{ $wonder->realm->number }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if ($wonder->realm)
                                            {{ number_format($wonderCalculator->getCurrentPower($wonder)) }}
                                        @else
                                            ???
                                        @endif
                                        / {{ number_format($wonder->power) }}
                                    </td>
                                    <td>
                                        {{ $wonderHelper->getWonderDescription($wonder->wonder) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($protectionService->isUnderProtection($selectedDominion))
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Invade</h3>
                    </div>
                    <div class="box-body">
                        You are currently under protection for
                        @if ($protectionService->getUnderProtectionHoursLeft($selectedDominion))
                            <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours
                        @else
                            <b>{{ $selectedDominion->protection_ticks_remaining }}</b> ticks
                        @endif
                        and may not attack wonders during that time.
                    </div>
                </div>
            @else
                <form action="{{ route('dominion.wonders') }}" method="post" role="form" id="attack_form">
                    @csrf
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Attack</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-xs-9 col-lg-10">
                                    <label for="target_wonder">Select a target</label>
                                    <select name="target_wonder" id="target_wonder" class="form-control select2" required style="width: 100%" data-placeholder="Select a target wonder" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        <option></option>
                                        @foreach ($wonders as $wonder)
                                            @if ($wonder->realm == null || $governmentService->isAtWarWithRealm($selectedDominion->realm, $wonder->realm))
                                                <option value="{{ $wonder->id }}" data-war="{{ $wonder->realm !== null ? 1 : 0 }}">
                                                    {{ $wonder->wonder->name }}
                                                    @if ($wonder->realm !== null)
                                                        (#{{ $wonder->realm->number }})
                                                    @endif
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xs-3 col-lg-2">
                                    @foreach ($spellHelper->getWonderSpells() as $spell)
                                        <div class="text-center" style="margin-top: 25px;">
                                            <button type="submit"
                                                    name="action"
                                                    value="{{ $spell['key'] }}"
                                                    class="btn btn-primary"
                                                    {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() ? 'disabled' : null }}>
                                                <i class="{{ $spell['icon_class'] }}"></i>
                                                {{ $spell['name'] }}
                                            </button>
                                            <div class="small text-center">
                                                @if ($spellCalculator->canCast($selectedDominion, $spell['key']))
                                                    Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell['key'])) }}</span>
                                                @else
                                                    Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell['key'])) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-users"></i> Units to send</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="10%">
                                    <col width="10%">
                                    <col width="10%">
                                    <col width="15%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th class="text-center">OP / DP</th>
                                        <th class="text-center">Available</th>
                                        <th class="text-center">Send</th>
                                        <th class="text-center">Total OP / DP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (range(1, 4) as $unitSlot)
                                        @php
                                            $unit = $selectedDominion->race->units->filter(function ($unit) use ($unitSlot) {
                                                return ($unit->slot === $unitSlot);
                                            })->first();
                                        @endphp

                                        @if ($unit->power_offense == 0)
                                            @continue
                                        @endif

                                        @php
                                            $offensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'offense');
                                            $defensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'defense');

                                            $hasDynamicOffensivePower = $unit->perks->filter(static function ($perk) {
                                                return starts_with($perk->key, ['offense_from_', 'offense_staggered_', 'offense_vs_']);
                                            })->count() > 0;
                                            $hasDynamicDefensivePower = $unit->perks->filter(static function ($perk) {
                                                return starts_with($perk->key, ['defense_from_', 'defense_staggered_', 'defense_vs_']);
                                            })->count() > 0;
                                        @endphp

                                        <tr>
                                            <td>
                                                {!! $unitHelper->getUnitTypeIconHtml("unit{$unitSlot}", $selectedDominion->race) !!}
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString("unit{$unitSlot}", $selectedDominion->race) }}">
                                                    {{ $unitHelper->getUnitName("unit{$unitSlot}", $selectedDominion->race) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span id="unit{{ $unitSlot }}_op">{{ (strpos($offensivePower, '.') !== false) ? number_format($offensivePower, 1) : number_format($offensivePower) }}</span>{{ $hasDynamicOffensivePower ? '*' : null }}
                                                /
                                                <span id="unit{{ $unitSlot }}_dp" class="text-muted">{{ (strpos($defensivePower, '.') !== false) ? number_format($defensivePower, 1) : number_format($defensivePower) }}</span><span class="text-muted">{{ $hasDynamicDefensivePower ? '*' : null }}</span>
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($selectedDominion->{"military_unit{$unitSlot}"}) }}
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="unit[{{ $unitSlot }}]"
                                                        id="unit[{{ $unitSlot }}]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0"
                                                        max="{{ $selectedDominion->{"military_unit{$unitSlot}"} }}"
                                                        data-slot="{{ $unitSlot }}"
                                                        data-amount="{{ $selectedDominion->{"military_unit{$unitSlot}"} }}"
                                                        data-op="{{ $unit->power_offense }}"
                                                        data-dp="{{ $unit->power_defense }}"
                                                        data-need-boat="{{ (int)$unit->need_boat }}"
                                                        {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td class="text-center" id="unit{{ $unitSlot }}_stats">
                                                <span class="op">0</span> / <span class="dp text-muted">0</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12 col-md-6">

                            <div class="box box-danger">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="ra ra-sword"></i> Invasion force</h3>
                                </div>
                                <div class="box-body table-responsive no-padding">
                                    <table class="table">
                                        <colgroup>
                                            <col width="50%">
                                            <col width="50%">
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>OP:</td>
                                                <td>
                                                    <strong id="invasion-force-op" data-amount="0">0</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>DP:</td>
                                                <td id="invasion-force-dp" data-amount="0">0</td>
                                            </tr>
                                            <tr>
                                                <td>Boats:</td>
                                                <td>
                                                    <span id="invasion-force-boats" data-amount="0">0</span>
                                                    /
                                                    {{ number_format(floor($selectedDominion->resource_boats)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    Max OP:
                                                    <i class="fa fa-question-circle"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="You may send out a maximum of 125% of your new home DP in OP. (5:4 rule)"></i>
                                                </td>
                                                <td id="invasion-force-max-op" data-amount="0">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-footer">
                                    <button type="submit"
                                            name="action"
                                            value="attack"
                                            id="attack-button"
                                            class="btn btn-danger"
                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() ? 'disabled' : null }}>
                                        <i class="ra ra-sword"></i>
                                        Attack
                                    </button>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-6">

                            <div class="box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-home"></i> New home forces</h3>
                                </div>
                                <div class="box-body table-responsive no-padding">
                                    <table class="table">
                                        <colgroup>
                                            <col width="50%">
                                            <col width="50%">
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>OP:</td>
                                                <td id="home-forces-op" data-original="{{ $militaryCalculator->getOffensivePower($selectedDominion) }}" data-amount="0">
                                                    {{ number_format($militaryCalculator->getOffensivePower($selectedDominion), 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>DP:</td>
                                                <td id="home-forces-dp" data-original="{{ $militaryCalculator->getDefensivePower($selectedDominion) }}" data-amount="0">
                                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion), 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Boats:</td>
                                                <td id="home-forces-boats" data-original="{{ floor($selectedDominion->resource_boats) }}" data-amount="0">
                                                    {{ number_format(floor($selectedDominion->resource_boats)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    Min DP:
                                                    <i class="fa fa-question-circle"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="You must leave at least 33% of your total DP at home. (33% rule)"></i>
                                                </td>
                                                <td id="home-forces-min-dp" data-amount="0">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </form>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Wonders provide bonuses to all dominions in the controlling realm and are acquired by destroying and rebuilding them.</p>
                    <p>The first wave of wonders will appear on the 6th day of the round with a starting power of 150,000. An additional wonder will appear every 48 hours with a starting power of 250,000. Once rebuilt, wonder power depends on the damage your realm did to it and time into the round.</p>
                    <p>Each dominion that participates in destroying a wonder that is controlled by another realm is awarded prestige.</p>
                    <p>The <a href="{{ route('dominion.magic') }}">Mindswell</a> spell can be used to imbue your troops with heightened awareness, uncovering the wonder's secrets upon invasion.</p>
                    <p>You have {{ number_format($selectedDominion->resource_mana) }} mana and {{ floor($selectedDominion->wizard_strength) }}% wizard strength.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            // Prevent accidental submit
            $(document).on("keydown", "form", function(event) { 
                return event.key != "Enter";
            });

            var invasionForceOPElement = $('#invasion-force-op');
            var invasionForceDPElement = $('#invasion-force-dp');
            var invasionForceBoatsElement = $('#invasion-force-boats');
            var invasionForceMaxOPElement = $('#invasion-force-max-op');
            var homeForcesOPElement = $('#home-forces-op');
            var homeForcesDPElement = $('#home-forces-dp');
            var homeForcesBoatsElement = $('#home-forces-boats');
            var homeForcesMinDPElement = $('#home-forces-min-dp');

            var invadeButtonElement = $('#attack-button');
            var allUnitInputs = $('input[name^=\'unit\']');

            $('#target_wonder').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            @if (!$protectionService->isUnderProtection($selectedDominion))
                updateUnitStats();
            @endif

            $('#target_wonder').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'calc\']').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'unit\']').change(function (e) {
                updateUnitStats();
            });

            @if (session('target_wonder'))
                $('#target_wonder').val('{{ session('target_wonder') }}').trigger('change.select2').trigger('change');
            @endif

            function updateUnitStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.dominion.invasion') }}?" + $('#attack_form').serialize(), {},
                    function(response) {
                        if(response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats data attributes
                                $('#unit\\['+slot+'\\]').data('dp', stats.dp);
                                $('#unit\\['+slot+'\\]').data('op', stats.op);
                                // Update unit stats display
                                $('#unit'+slot+'_dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                                $('#unit'+slot+'_op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update OP / DP data attributes
                            invasionForceOPElement.data('amount', response.away_offense);
                            invasionForceDPElement.data('amount', response.away_defense);
                            invasionForceBoatsElement.data('amount', response.boats_needed);
                            invasionForceMaxOPElement.data('amount', response.max_op);
                            homeForcesOPElement.data('amount', response.home_offense);
                            homeForcesDPElement.data('amount', response.home_defense);
                            homeForcesBoatsElement.data('amount', response.boats_remaining);
                            homeForcesMinDPElement.data('amount', response.min_dp);
                            // Update OP / DP display
                            invasionForceOPElement.text(response.away_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceDPElement.text(response.away_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceBoatsElement.text(response.boats_needed.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceMaxOPElement.text(response.max_op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesOPElement.text(response.home_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPElement.text(response.home_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesBoatsElement.text(response.boats_remaining.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesMinDPElement.text(response.min_dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            calculate();
                        }
                    }
                );
            }

            function calculate() {
                // Calculate subtotals for each unit
                allUnitInputs.each(function () {
                    var unitOP = parseFloat($(this).data('op'));
                    var unitDP = parseFloat($(this).data('dp'));
                    var amountToSend = parseInt($(this).val() || 0);
                    var totalUnitOP = amountToSend * unitOP;
                    var totalUnitDP = amountToSend * unitDP;
                    var unitSlot = parseInt($(this).data('slot'));
                    var unitStatsElement = $('#unit' + unitSlot + '_stats');
                    unitStatsElement.find('.op').text(totalUnitOP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                    unitStatsElement.find('.dp').text(totalUnitDP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                });

                // Check if we have enough of these bad bois
                /*                __--___
                                 >_'--'__'
                                _________!__________
                               /   /   /   /   /   /
                              /   /   /   /   /   /
                             |   |   |   |   |   |
                        __^  |   |   |   |   |   |
                      _/@  \  \   \   \   \   \   \
                     S__   |   \   \   \   \   \   \         __
                    (   |  |    \___\___\___\___\___\       /  \
                        |   \             |                |  |\|
                        \    \____________!________________/  /
                         \ _______OOOOOOOOOOOOOOOOOOO________/
                          \________\\\\\\\\\\\\\\\\\\_______/
                %%%^^^^^%%%%%^^^^!!^%%^^^^%%%%%!!!!^^^^^^!%^^^%%%%!!^^
                ^^!!!!%%%%^^^^!!^^%%%%%^^!!!^^%%%%%!!!%%%%^^^!!^^%%%!!

                Shamelessly stolen from http://www.asciiworld.com/-Boats-.html */

                var hasEnoughBoats = parseInt(invasionForceBoatsElement.data('amount')) <= {{ floor($selectedDominion->resource_boats) }};
                if (!hasEnoughBoats) {
                    invasionForceBoatsElement.addClass('text-danger');
                    homeForcesBoatsElement.addClass('text-danger');
                } else {
                    invasionForceBoatsElement.removeClass('text-danger');
                    homeForcesBoatsElement.removeClass('text-danger');
                }

                // Check 33% rule
                var minDefenseRule = parseFloat(homeForcesDPElement.data('amount')) < parseFloat(homeForcesMinDPElement.data('amount'));
                if (minDefenseRule) {
                    homeForcesDPElement.addClass('text-danger');
                } else {
                    homeForcesDPElement.removeClass('text-danger');
                }

                // Check 5:4 rule
                var maxOffenseRule = parseFloat(invasionForceOPElement.data('amount')) > parseFloat(invasionForceMaxOPElement.data('amount'));
                if (maxOffenseRule) {
                    invasionForceOPElement.addClass('text-danger');
                } else {
                    invasionForceOPElement.removeClass('text-danger');
                }

                // Check if invade button should be disabled
                if (!hasEnoughBoats || maxOffenseRule || {{ $selectedDominion->round->hasOffensiveActionsDisabled() ? 1 : 0 }}) {
                    invadeButtonElement.attr('disabled', 'disabled');
                } else {
                    invadeButtonElement.removeAttr('disabled');
                }
            }
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const war = state.element.dataset.war;

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${warStatus}
                <div style="clear:both;"></div>
            `);
        }
    </script>
@endpush
