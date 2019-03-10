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
                <form action="{{ route('dominion.invade') }}" method="post" role="form">
                    @csrf

                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Invade</h3>
                        </div>
                        <div class="box-body">
                            <div class="form-group">
                                <label for="target_dominion">Select a target</label>
                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion">
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
                                        <th class="text-center">Raw OP / DP</th>
                                        <th class="text-center">Trained</th>
                                        <th class="text-center">Send</th>
                                        <th class="text-center">Net OP / DP</th>
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
                                                {{ $unitHelper->getUnitName("unit{$unitSlot}", $selectedDominion->race) }}
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($unit->power_offense) }}
                                                /
                                                <span class="text-muted">
                                                    {{ number_format($unit->power_defense) }}
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
                                                       data-dp="{{ $unit->power_defense }}">
                                            </td>
                                            <td class="text-center" id="unit{{ $unitSlot }}_stats">
                                                <span class="op">0</span> / <span class="dp">0</span>
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
                                <div class="box-body no-padding">
                                    <table class="table">
                                        <colgroup>
                                            <col width="50%">
                                            <col width="50%">
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>Net OP:</td>
                                                <td>
                                                    <strong id="invasion-force-op">0</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Net DP:</td>
                                                <td id="invasion-force-dp">0</td>
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
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-6">

                            <div class="box">
                                <div class="box-header with-border">
                                    <h3 class="box-title"><i class="fa fa-home"></i> New home forces</h3>
                                </div>
                                <div class="box-body no-padding">
                                    <table class="table">
                                        <colgroup>
                                            <col width="50%">
                                            <col width="50%">
                                        </colgroup>
                                        <tbody>
                                            <tr>
                                                <td>Net OP:</td>
                                                <td id="home-forces-op" data-original="{{ $militaryCalculator->getOffensivePower($selectedDominion) }}">
                                                    {{ number_format($militaryCalculator->getOffensivePower($selectedDominion)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Net DP:</td>
                                                <td id="home-forces-dp" data-original="{{ $militaryCalculator->getDefensivePower($selectedDominion) }}">
                                                    {{ number_format($militaryCalculator->getDefensivePower($selectedDominion)) }}
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
                                                <td>0</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <button type="submit"
                            class="btn btn-primary"
                            {{ $selectedDominion->isLocked() ? 'disabled' : null }}
                            id="invade-button">
                        Invade
                    </button>

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

            $('input[name^=\'unit\']').change(function (e) {
                // var input = $(this);
                var invasionForceOPElement = $('#invasion-force-op');
                var homeForcesOPElement = $('#home-forces-op');
                var homeForcesDPElement = $('#home-forces-dp');
                var invadeButtonElement = $('#invade-button');
                var allUnitInputs = $('input[name^=\'unit\']');
                var unitSlot = parseInt($(this).data('slot'));

                var invadingForceOP = 0;
                var invadingForceDP = 0;
                var originalHomeForcesOP = parseInt(homeForcesOPElement.data('original'));
                var originalHomeForcesDP = parseInt(homeForcesDPElement.data('original'));
                var newHomeForcesOP;
                var newHomeForcesDP;

                var OPMultiplier = parseFloat('{{ $militaryCalculator->getOffensivePowerMultiplier($selectedDominion) }}');
                var DPMultiplier = parseFloat('{{ $militaryCalculator->getDefensivePowerMultiplier($selectedDominion) }}');

                var DPNeededToLeaveAtHome; // 33% rule
                var allowedMaxOP; // 5:4 rule

                // Calculate total unit OP / DP
                var unitStatsElement = $('#unit' + unitSlot + '_stats');
                unitStatsElement.find('.op').text((parseInt($(this).val() || 0) * (parseFloat($(this).data('op')) * OPMultiplier)).toLocaleString());
                unitStatsElement.find('.dp').text((parseInt($(this).val() || 0) * (parseFloat($(this).data('dp')) * DPMultiplier)).toLocaleString());

                // Calculate invading force OP / DP
                allUnitInputs.each(function () {
                    // var unitAmount = parseInt($(this).data('amount')); // total amount at home before invading
                    var unitOP = parseFloat($(this).data('op'));
                    var unitDP = parseFloat($(this).data('dp'));
                    var amountToSend = parseInt($(this).val() || 0);

                    if (amountToSend === 0) {
                        return true; // continue
                    }

                    invadingForceOP += (amountToSend * unitOP) * OPMultiplier;
                    invadingForceDP += (amountToSend * unitDP) * DPMultiplier;
                });

                DPNeededToLeaveAtHome = Math.floor(invadingForceOP / 3);
                allowedMaxOP = Math.floor((originalHomeForcesDP - invadingForceDP) * 1.25);

                newHomeForcesOP = originalHomeForcesOP - invadingForceOP;
                newHomeForcesDP = originalHomeForcesDP - invadingForceDP;

                invasionForceOPElement.text(invadingForceOP.toLocaleString());
                $('#invasion-force-max-op').text(allowedMaxOP.toLocaleString());
                $('#invasion-force-dp').text(invadingForceDP.toLocaleString());
                homeForcesOPElement.text(newHomeForcesOP.toLocaleString());
                homeForcesDPElement.text(newHomeForcesDP.toLocaleString());
                $('#home-forces-min-dp').text(DPNeededToLeaveAtHome.toLocaleString());

                // 33% rule
                if (newHomeForcesDP < DPNeededToLeaveAtHome) {
                    homeForcesDPElement.addClass('text-danger');
                    invadeButtonElement.attr('disabled', 'disabled');
                } else {
                    homeForcesDPElement.removeClass('text-danger');
                    invadeButtonElement.removeAttr('disabled');
                }

                // 5:4 rule
                if (invadingForceOP > allowedMaxOP) {
                    invasionForceOPElement.addClass('text-danger');
                    invadeButtonElement.attr('disabled', 'disabled');
                } else {
                    invasionForceOPElement.removeClass('text-danger');
                    invadeButtonElement.removeAttr('disabled');
                }
            });
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
