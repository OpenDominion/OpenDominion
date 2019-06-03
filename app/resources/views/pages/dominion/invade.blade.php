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
                        You are currently under protection for <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours and may not invade during that time.
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
                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                        <option value="{{ $dominion->id }}"
                                                data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}">
                                            {{ $dominion->name }} (#{{ $dominion->realm->number }})
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
                                    <col width="100">
                                    <col width="100">
                                    <col width="100">
                                    <col width="150">
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

                                        <tr>
                                            <td>
                                                {!! $unitHelper->getUnitTypeIconHtml("unit{$unitSlot}") !!}
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString("unit{$unitSlot}", $selectedDominion->race) }}">
                                                    {{ $unitHelper->getUnitName("unit{$unitSlot}", $selectedDominion->race) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span id="unit{{ $unitSlot }}_op">
                                                    {{ (strpos($unit->power_offense, ".") !== false) ? number_format($unit->power_offense, 1) : number_format($unit->power_offense) }}
                                                </span>
                                                /
                                                <span id="unit{{ $unitSlot }}_dp" class="text-muted">
                                                    {{ (strpos($unit->power_defense, ".") !== false) ? number_format($unit->power_defense, 1) : number_format($unit->power_defense) }}
                                                </span>
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
                                                    <strong id="invasion-force-op">0</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>DP:</td>
                                                <td id="invasion-force-dp">0</td>
                                            </tr>
                                            <tr>
                                                <td>Boats:</td>
                                                <td>
                                                    <span id="invasion-force-boats">0</span>
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
                                                <td id="invasion-force-max-op">0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="box-footer">
                                    <button type="submit"
                                            class="btn btn-danger"
                                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                                            id="invade-button">
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
                                                <td id="home-forces-op" data-original="{{ $militaryCalculator->getOffensivePower($selectedDominion) }}">
                                                    {{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>DP:</td>
                                                <td id="home-forces-dp" data-original="{{ $militaryCalculator->getDefensivePower($selectedDominion) }}">
                                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Boats:</td>
                                                <td id="home-forces-boats" data-original="{{ floor($selectedDominion->resource_boats) }}">
                                                    {{ number_format(floor($selectedDominion->resource_boats)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    Min DP:
                                                    <i class="fa fa-question-circle"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="You must leave at least 33% of your invasion force OP in DP at home. (33% rule)"></i>
                                                </td>
                                                <td id="home-forces-min-dp">0</td>
                                            </tr>
                                            <tr>
                                                <td>DPA:</td>
                                                <td id="home-forces-dpa">
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
                    <p>You can only invade dominions that are within your range, and you will only gain prestige on targets 75% or greater relative to your own land size.</p>
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
            $('.select2').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });

            updateUnitStats();

            $('#target_dominion').change(function (e) {
                updateUnitStats();
            });

            // please forgive me for this monstrosity
            $('input[name^=\'unit\']').change(function (e) {
                calculate();
            });

            function updateUnitStats() {
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
                            calculate();
                        }
                    }
                );
            }

            function calculate() {
                var invasionForceOPElement = $('#invasion-force-op');
                var invasionForceBoatsElement = $('#invasion-force-boats');
                var homeForcesOPElement = $('#home-forces-op');
                var homeForcesDPElement = $('#home-forces-dp');
                var homeForcesBoatsElement = $('#home-forces-boats');
                var invadeButtonElement = $('#invade-button');
                var allUnitInputs = $('input[name^=\'unit\']');

                var invadingForceOP = 0;
                var invadingForceDP = 0;
                var invadingForceBoats = 0;
                var originalHomeForcesOP = parseInt(homeForcesOPElement.data('original'));
                var originalHomeForcesDP = parseInt(homeForcesDPElement.data('original'));
                var originalHomeForcesBoats = parseInt(homeForcesBoatsElement.data('original'));
                var newHomeForcesOP;
                var newHomeForcesDP;
                var newHomeForcesBoats;
                var newDPA;

                var landSize = parseInt('{{ $landCalculator->getTotalLand($selectedDominion) }}');
                var OPMultiplier = parseFloat('{{ $militaryCalculator->getOffensivePowerMultiplier($selectedDominion) }}');
                var DPMultiplier = parseFloat('{{ $militaryCalculator->getDefensivePowerMultiplier($selectedDominion) }}');

                var DPNeededToLeaveAtHome; // 33% rule
                var allowedMaxOP; // 5:4 rule

                // Calculate invading force OP / DP
                allUnitInputs.each(function () {
                    // var unitAmount = parseInt($(this).data('amount')); // total amount at home before invading
                    var unitOP = parseFloat($(this).data('op'));
                    var unitDP = parseFloat($(this).data('dp'));
                    var amountToSend = parseInt($(this).val() || 0);
                    var needBoat = !!$(this).data('need-boat');

                    var totalUnitOP = amountToSend * unitOP * OPMultiplier;
                    var totalUnitDP = amountToSend * unitDP * DPMultiplier;
                    var unitSlot = parseInt($(this).data('slot'));
                    var unitStatsElement = $('#unit' + unitSlot + '_stats');
                    unitStatsElement.find('.op').text(totalUnitOP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                    unitStatsElement.find('.dp').text(totalUnitDP.toLocaleString(undefined, {maximumFractionDigits: 2}));

                    if (amountToSend === 0) {
                        return true; // continue
                    }

                    invadingForceOP += (amountToSend * unitOP) * OPMultiplier;
                    invadingForceDP += (amountToSend * unitDP) * DPMultiplier;

                    if (needBoat) {
                        invadingForceBoats += amountToSend;
                    }
                });

                invadingForceBoats = Math.ceil(invadingForceBoats / 30);

                DPNeededToLeaveAtHome = Math.floor(invadingForceOP / 3);
                allowedMaxOP = Math.ceil((originalHomeForcesDP - invadingForceDP) * 1.25);

                newHomeForcesOP = Math.round(originalHomeForcesOP - invadingForceOP);
                newHomeForcesDP = Math.round(originalHomeForcesDP - invadingForceDP);
                newHomeForcesBoats = originalHomeForcesBoats - invadingForceBoats;
                newDPA = newHomeForcesDP / landSize;

                invasionForceOPElement.text(invadingForceOP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                $('#invasion-force-dp').text(invadingForceDP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                invasionForceBoatsElement.text(invadingForceBoats.toLocaleString());
                $('#invasion-force-max-op').text(allowedMaxOP.toLocaleString());
                homeForcesOPElement.text(newHomeForcesOP.toLocaleString());
                homeForcesDPElement.text(newHomeForcesDP.toLocaleString());
                homeForcesBoatsElement.text(newHomeForcesBoats.toLocaleString());
                $('#home-forces-min-dp').text(DPNeededToLeaveAtHome.toLocaleString());
                $('#home-forces-dpa').text(newDPA.toLocaleString(undefined, {minimumFractionDigits: 3, maximumFractionDigits: 3}));

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
                if (invadingForceBoats > originalHomeForcesBoats) {
                    invasionForceBoatsElement.addClass('text-danger');
                    homeForcesBoatsElement.addClass('text-danger');
                } else {
                    invasionForceBoatsElement.removeClass('text-danger');
                    homeForcesBoatsElement.removeClass('text-danger');
                }

                // 33% rule
                if (newHomeForcesDP < DPNeededToLeaveAtHome) {
                    homeForcesDPElement.addClass('text-danger');
                } else {
                    homeForcesDPElement.removeClass('text-danger');
                }

                // 5:4 rule
                if (invadingForceOP > allowedMaxOP) {
                    invasionForceOPElement.addClass('text-danger');
                } else {
                    invasionForceOPElement.removeClass('text-danger');
                }

                // Check if invade button should be disabled
                if (
                    (invadingForceBoats > originalHomeForcesBoats) ||
                    (newHomeForcesDP < DPNeededToLeaveAtHome) ||
                    (invadingForceOP > allowedMaxOP)
                ) {
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
            let difficultyClass;

            if (percentage >= 133) {
                difficultyClass = 'text-red';
            } else if (percentage >= 120) {
                difficultyClass = 'text-orange';
            } else if (percentage >= 75) {
                difficultyClass = 'text-yellow';
            } else if (percentage >= 66) {
                difficultyClass = 'text-green';
            } else {
                difficultyClass = 'text-muted';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                <div class="pull-right">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
