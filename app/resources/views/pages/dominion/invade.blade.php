@extends ('layouts.master')

@section('page-header', 'Invade')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
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
                        and may not invade during that time.
                    </div>
                </div>
            @elseif ($selectedDominion->morale < 70)
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Invade</h3>
                    </div>
                    <div class="box-body">
                        Your military needs at least 70% morale to invade others. Your military currently has {{ $selectedDominion->morale }}% morale.
                    </div>
                </div>
            @else
                <form action="{{ route('dominion.invade') }}" method="post" role="form" id="invade_form">
                    @csrf

                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Invade</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="target_dominion">Select a target</label>
                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    <option></option>
                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion, false) as $dominion)
                                        <option value="{{ $dominion->id }}"
                                                data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}"
                                                data-war="{{ $governmentService->isAtWarWithRealm($selectedDominion->realm, $dominion->realm) ? 1 : 0 }}">
                                            {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
                                        </option>
                                    @endforeach
                                </select>
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
                                    @php
                                        $offenseVsBuildingTypes = [];
                                    @endphp
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
                                            if ($hasDynamicOffensivePower) {
                                                $offenseVsBuildingPerk = $unit->getPerkValue('offense_vs_building');
                                                if ($offenseVsBuildingPerk) {
                                                    $offenseVsBuildingTypes[] = explode(',', $offenseVsBuildingPerk)[0];
                                                }
                                            }
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
                                    @foreach ($offenseVsBuildingTypes as $buildingType)
                                        <tr>
                                            <td colspan="3" class="text-right">
                                                <b>Enter target {{ ucwords(str_replace('_', ' ', $buildingType)) }} percentage:</b>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       step="any"
                                                       name="calc[target_{{ $buildingType }}_percent]"
                                                       class="form-control text-center"
                                                       min="0"
                                                       max="100"
                                                       placeholder="0"
                                                       {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                            <td>&nbsp;</td>
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
                                            <tr>
                                                <td>
                                                    Target Min DP:
                                                    <i class="fa fa-question-circle"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="The minimum defense for a dominion is 3x their land size."></i>
                                                </td>
                                                <td id="target-min-dp" data-amount="0">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-footer">
                                    <button type="submit"
                                            class="btn btn-danger"
                                            id="invade-button"
                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() ? 'disabled' : null }}>
                                        <i class="ra ra-crossed-swords"></i>
                                        Invade
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
                                            <tr>
                                                <td>DPA:</td>
                                                <td id="home-forces-dpa" data-amount="0">
                                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion) / $landCalculator->getTotalLand($selectedDominion), 3) }}
                                                </td>
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
                    <p>Here you can invade other players to try to capture some of their land and to gain prestige. Invasions are successful if you send more OP than they have DP.</p>
                    <p>Find targets using <a href="{{ route('dominion.magic') }}">magic</a>,  <a href="{{ route('dominion.espionage') }}">espionage</a> and the <a href="{{ route('dominion.op-center') }}">Op Center</a>. Communicate with your realmies using the <a href="{{ route('dominion.council') }}">council</a> to coordinate attacks.</p>
                    <p>Be sure to calculate your OP vs your target's DP to avoid blindly sending your units to their doom.</p>
                    <p>You can only invade dominions that are within your range, and you will only gain prestige and discounted construction on targets <b>75% or greater</b> relative to your own land size.</p>
                    @if ($selectedDominion->morale < 100)
                        <p>You have {{ $selectedDominion->morale }}% morale, which is reducing your offense and defense by {{ number_format(100 - $militaryCalculator->getMoraleMultiplier($selectedDominion) * 100, 2) }}%.</p>
                    @else
                        <p>You have {{ $selectedDominion->morale }}% morale.</p>
                    @endif
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
            var targetMinDPElement = $('#target-min-dp');
            var homeForcesOPElement = $('#home-forces-op');
            var homeForcesDPElement = $('#home-forces-dp');
            var homeForcesBoatsElement = $('#home-forces-boats');
            var homeForcesMinDPElement = $('#home-forces-min-dp');
            var homeForcesDPAElement = $('#home-forces-dpa');

            var invadeButtonElement = $('#invade-button');
            var allUnitInputs = $('input[name^=\'unit\']');

            $('#target_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            @if (!$protectionService->isUnderProtection($selectedDominion))
                updateUnitStats();
            @endif

            $('#target_dominion').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'calc\']').change(function (e) {
                updateUnitStats();
            });

            $('input[name^=\'unit\']').change(function (e) {
                updateUnitStats();
            });

            function updateUnitStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.dominion.invasion') }}?" + $('#invade_form').serialize(), {},
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
                            targetMinDPElement.data('amount', response.target_min_dp);
                            homeForcesOPElement.data('amount', response.home_offense);
                            homeForcesDPElement.data('amount', response.home_defense);
                            homeForcesBoatsElement.data('amount', response.boats_remaining);
                            homeForcesMinDPElement.data('amount', response.min_dp);
                            homeForcesDPAElement.data('amount', response.home_dpa);
                            // Update OP / DP display
                            invasionForceOPElement.text(response.away_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceDPElement.text(response.away_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceBoatsElement.text(response.boats_needed.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            invasionForceMaxOPElement.text(response.max_op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            targetMinDPElement.text(response.target_min_dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesOPElement.text(response.home_offense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPElement.text(response.home_defense.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesBoatsElement.text(response.boats_remaining.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesMinDPElement.text(response.min_dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            homeForcesDPAElement.text(response.home_dpa.toLocaleString(undefined, {maximumFractionDigits: 3}));
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

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            const war = state.element.dataset.war;
            let difficultyClass;

            if (percentage >= 120) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 66) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${warStatus}
                <div class="pull-right">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
