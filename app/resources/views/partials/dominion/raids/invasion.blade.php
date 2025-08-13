@foreach ($tactics as $tactic)
    <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post" role="form" id="invasion_form_{{ $tactic->id }}">
        @csrf
        <input type="hidden" name="calc[wonder]" value="1" />
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="box-title"><i class="ra ra-crossed-swords"></i> {{ $tactic->name }}</div>
                <div class="box-tools pull-right">
                    <div class="label label-primary">Invasion</div>
                </div>
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
                                    <span id="unit{{ $unitSlot }}_op_{{ $tactic->id }}">{{ (strpos($offensivePower, '.') !== false) ? number_format($offensivePower, 1) : number_format($offensivePower) }}</span>{{ $hasDynamicOffensivePower ? '*' : null }}
                                    /
                                    <span id="unit{{ $unitSlot }}_dp_{{ $tactic->id }}" class="text-muted">{{ (strpos($defensivePower, '.') !== false) ? number_format($defensivePower, 1) : number_format($defensivePower) }}</span><span class="text-muted">{{ $hasDynamicDefensivePower ? '*' : null }}</span>
                                </td>
                                <td class="text-center">
                                    {{ number_format($selectedDominion->{"military_unit{$unitSlot}"}) }}
                                </td>
                                <td class="text-center">
                                    <input type="number"
                                            name="unit[{{ $unitSlot }}]"
                                            id="unit[{{ $unitSlot }}]_{{ $tactic->id }}"
                                            class="form-control text-center"
                                            placeholder="0"
                                            min="0"
                                            max="{{ $selectedDominion->{"military_unit{$unitSlot}"} }}"
                                            data-slot="{{ $unitSlot }}"
                                            data-tactic="{{ $tactic->id }}"
                                            data-amount="{{ $selectedDominion->{"military_unit{$unitSlot}"} }}"
                                            data-op="{{ $unit->power_offense }}"
                                            data-dp="{{ $unit->power_defense }}"
                                            data-need-boat="{{ (int)$unit->need_boat }}"
                                            {{ $selectedDominion->isLocked() || !$objective->raid->isActive() ? 'disabled' : null }}>
                                </td>
                                <td class="text-center" id="unit{{ $unitSlot }}_stats_{{ $tactic->id }}">
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
                        <div class="box-title"><i class="ra ra-sword"></i> Invasion force</div>
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
                                        <strong id="invasion-force-op-{{ $tactic->id }}" data-amount="0">0</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>DP:</td>
                                    <td id="invasion-force-dp-{{ $tactic->id }}" data-amount="0">0</td>
                                </tr>
                                <tr>
                                    <td>Boats:</td>
                                    <td>
                                        <span id="invasion-force-boats-{{ $tactic->id }}" data-amount="0">0</span>
                                        /
                                        {{ number_format(rfloor($selectedDominion->resource_boats)) }}
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
                                    <td id="invasion-force-max-op-{{ $tactic->id }}" data-amount="0">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit"
                                id="attack-button-{{ $tactic->id }}"
                                class="btn btn-danger"
                                {{ $selectedDominion->isLocked() || !$objective->raid->isActive() ? 'disabled' : null }}>
                            <i class="ra ra-sword"></i>
                            Attack
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-6">
                <div class="box">
                    <div class="box-header with-border">
                        <div class="box-title"><i class="fa fa-home"></i> New home forces</div>
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
                                    <td id="home-forces-op-{{ $tactic->id }}" data-original="{{ $militaryCalculator->getOffensivePower($selectedDominion) }}" data-amount="0">
                                        {{ number_format($militaryCalculator->getOffensivePower($selectedDominion), 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>DP:</td>
                                    <td id="home-forces-dp-{{ $tactic->id }}" data-original="{{ $militaryCalculator->getDefensivePower($selectedDominion) }}" data-amount="0">
                                        {{ number_format($militaryCalculator->getDefensivePower($selectedDominion), 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Boats:</td>
                                    <td id="home-forces-boats-{{ $tactic->id }}" data-original="{{ rfloor($selectedDominion->resource_boats) }}" data-amount="0">
                                        {{ number_format(rfloor($selectedDominion->resource_boats)) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Min DP:
                                        <i class="fa fa-question-circle"
                                            data-toggle="tooltip"
                                            data-placement="top"
                                            title="You must leave at least 40% of your total DP at home. (40% rule)"></i>
                                    </td>
                                    <td id="home-forces-min-dp-{{ $tactic->id }}" data-amount="0">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('inline-scripts')
        <script type="text/javascript">
            (function ($) {
                var tacticId = {{ $tactic->id }};
                
                // Prevent accidental submit
                $(document).on("keydown", "#invasion_form_" + tacticId, function(event) { 
                    return event.key != "Enter";
                });

                var invasionForceOPElement = $('#invasion-force-op-' + tacticId);
                var invasionForceDPElement = $('#invasion-force-dp-' + tacticId);
                var invasionForceBoatsElement = $('#invasion-force-boats-' + tacticId);
                var invasionForceMaxOPElement = $('#invasion-force-max-op-' + tacticId);
                var homeForcesOPElement = $('#home-forces-op-' + tacticId);
                var homeForcesDPElement = $('#home-forces-dp-' + tacticId);
                var homeForcesBoatsElement = $('#home-forces-boats-' + tacticId);
                var homeForcesMinDPElement = $('#home-forces-min-dp-' + tacticId);

                var attackButtonElement = $('#attack-button-' + tacticId);
                var allUnitInputs = $('input[data-tactic="' + tacticId + '"]');

                updateUnitStats();

                $('input[data-tactic="' + tacticId + '"]').change(function (e) {
                    updateUnitStats();
                });

                function updateUnitStats() {
                    // Update unit stats
                    $.get(
                        "{{ route('api.dominion.invasion') }}?" + $('#invasion_form_' + tacticId).serialize(), {},
                        function(response) {
                            if (response.result == 'success') {
                                $.each(response.units, function(slot, stats) {
                                    // Update unit stats data attributes
                                    $('#unit\\['+slot+'\\]_' + tacticId).data('dp', stats.dp);
                                    $('#unit\\['+slot+'\\]_' + tacticId).data('op', stats.op);
                                    // Update unit stats display
                                    $('#unit'+slot+'_dp_' + tacticId).text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                                    $('#unit'+slot+'_op_' + tacticId).text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
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
                        var unitStatsElement = $('#unit' + unitSlot + '_stats_' + tacticId);
                        unitStatsElement.find('.op').text(totalUnitOP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                        unitStatsElement.find('.dp').text(totalUnitDP.toLocaleString(undefined, {maximumFractionDigits: 2}));
                    });

                    // Check if we have enough boats
                    var hasEnoughBoats = parseInt(invasionForceBoatsElement.data('amount')) <= {{ rfloor($selectedDominion->resource_boats) }};
                    if (!hasEnoughBoats) {
                        invasionForceBoatsElement.addClass('text-danger');
                        homeForcesBoatsElement.addClass('text-danger');
                    } else {
                        invasionForceBoatsElement.removeClass('text-danger');
                        homeForcesBoatsElement.removeClass('text-danger');
                    }

                    // Check 40% rule
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

                    // Check if attack button should be disabled
                    if (!hasEnoughBoats || maxOffenseRule || minDefenseRule || {{ $selectedDominion->round->hasOffensiveActionsDisabled() ? 1 : 0 }}) {
                        attackButtonElement.attr('disabled', 'disabled');
                    } else {
                        attackButtonElement.removeAttr('disabled');
                    }
                }
            })(jQuery);
        </script>
    @endpush
@endforeach
