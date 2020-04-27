@extends ('layouts.master')

@section('page-header', 'Calculate Defense')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <form action="" method="get" role="form" id="calculate_form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calculator"></i> Defense Calculator</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <div class="form-group">
                            <label for="race">Race</label>
                            <select name="race" id="race" class="form-control" style="width: 100%;">
                                <option>Select a race</option>
                                @foreach ($races as $race)
                                    <option value="{{ $race->id }}">
                                        {{ $race->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="150">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th class="text-center">Home</th>
                                    <th class="text-center">Subtotal</th>
                                </tr>
                                <tr>
                                    <td>
                                        Draftees
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                                name="calc[draftees]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0" />
                                    </td>
                                    <td class="text-center" id="draftees_stats">
                                        0
                                    </td>
                                </tr>
                            </thead>
                            @foreach ($races as $race)
                                @php
                                    $buildingFieldsRequired = [];
                                    $landFieldsRequired = [];
                                    $prestigeRequired = false;
                                @endphp
                                <thead id="race_{{ $race->id }}" class="race_units" style="display: none;">
                                    @foreach ($race->units()->orderBy('slot')->get() as $unit)
                                        @php
                                            $buildingPerks = $unit->perks->where('key', 'defense_from_building');
                                            foreach ($buildingPerks as $perk) {
                                                $building = explode(',', $perk->pivot->value)[0];
                                                if (!isset($buildingFieldsRequired[$building])) {
                                                    $buildingFieldsRequired[] = $building;
                                                }
                                            }
                                            $landPerks = $unit->perks->where('key', 'defense_from_land');
                                            foreach ($landPerks as $perk) {
                                                $land = explode(',', $perk->pivot->value)[0];
                                                if (!isset($landFieldsRequired[$building])) {
                                                    $landFieldsRequired[] = $land;
                                                }
                                            }
                                            if ($unit->perks->where('key', 'defense_from_prestige')->count()) {
                                                $prestigeRequired = true;
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                {!! $unitHelper->getUnitTypeIconHtml("unit{$unit->slot}", $race) !!}
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString("unit{$unit->slot}", $race) }}">
                                                    {{ $unitHelper->getUnitName("unit{$unit->slot}", $race) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[unit{{ $unit->slot }}]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0"
                                                        data-slot="{{ $unit->slot }}"
                                                        data-dp="{{ $unit->power_defense }}"
                                                        disabled />
                                            </td>
                                            <td class="text-center" id="unit{{ $unit->slot }}_stats">
                                                <span class="dp">0</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($buildingFieldsRequired as $building)
                                        <tr>
                                            <td>
                                                {{ ucwords(dominion_attr_display("building_{$building}")) }}
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        step="any"
                                                        name="calc[{{ $building }}_percent]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0"
                                                        value="50" />
                                            </td>
                                            <td class="text-center" id="{{ $building }}_percent_stats">
                                                0
                                            </td>
                                        </tr>
                                    @endforeach
                                    @foreach ($landFieldsRequired as $land)
                                        <tr>
                                            <td>
                                                {{ ucwords(dominion_attr_display("land_{$land}")) }}
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        step="any"
                                                        name="calc[{{ $land }}_percent]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0"
                                                        value="60" />
                                            </td>
                                            <td class="text-center" id="{{ $land }}_percent_stats">
                                                0
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($prestigeRequired)
                                        <tr>
                                            <td>
                                                Prestige
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[prestige]"
                                                        class="form-control text-center"
                                                        placeholder="250"
                                                        min="0"
                                                        max="250"
                                                        value="250" />
                                            </td>
                                            <td class="text-center" id="prestige_stats">
                                                0
                                            </td>
                                        </tr>
                                    @endif
                                    @php
                                        $racialSpell = $spellHelper->getRacialSelfSpell($race);
                                    @endphp
                                    @if (in_array($racialSpell['key'], ['blizzard', 'defensive_frenzy', 'howling']))
                                        <tr>
                                            <td>
                                                {{ $racialSpell['name'] }}
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox"
                                                        step="any"
                                                        name="calc[{{ $racialSpell['key'] }}]"
                                                        id="{{ $racialSpell['key'] }}"
                                                        checked />
                                            </td>
                                            <td class="text-center" id="{{ $racialSpell['key'] }}_stats">
                                                0
                                            </td>
                                        </tr>
                                    @endif
                                </thead>
                            @endforeach
                            <tbody>
                                <tr>
                                    <td>
                                        Ares Call
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox"
                                                step="any"
                                                name="calc[ares_call]"
                                                id="ares_call"
                                                checked />
                                    </td>
                                    <td class="text-center" id="ares_call_stats">
                                        0
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Morale
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                                name="calc[morale]"
                                                class="form-control text-center"
                                                placeholder="100"
                                                min="0"
                                                max="100"
                                                value="100" />
                                    </td>
                                    <td class="text-center" id="morale_stats">
                                        0
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Walls
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                                step="any"
                                                name="calc[walls_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0" />
                                    </td>
                                    <td class="text-center" id="walls_percent_stats">
                                        0
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Guard Tower
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                                step="any"
                                                name="calc[guard_tower_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                max="20" />
                                    </td>
                                    <td class="text-center" id="guard_tower_percent_stats">
                                        0
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Attacker's Temples
                                    </td>
                                    <td class="text-center">
                                        <input type="number"
                                                step="any"
                                                name="calc[temple_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                max="16.67" />
                                    </td>
                                    <td class="text-center" id="temple_percent_stats">
                                        0
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>
                                        <button class="btn btn-primary btn-block" type="button" id="calculate-button">Calculate</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Results</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table">
                        <tbody>
                            <tr style="font-weight: bold;">
                                <td>Total Defense:</td>
                                <td id="dp">--</td>
                            </tr>
                            <tr>
                                <td>Defensive Multiplier:</b></td>
                                <td id="dp-multiplier">--</td>
                            </tr>
                            <tr>
                                <td>Raw Defense:</td>
                                <td id="dp-raw">--</td>
                            </tr>
                        </tbody>
                    </table>
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
            var DPTotalElement = $('#dp');
            var DPMultiplierElement = $('#dp-multiplier');
            var DPRawElement = $('#dp-raw');

            //var allUnitInputs = $('input[name^=\'unit\']');

            $('#race').select2().change(function (e) {
                // Toggle racial units fields
                $('.race_units').hide();
                $('.race_units input').prop('disabled', true);
                $('#race_' + $(this).val() + ' input').prop('disabled', false);
                $('#race_' + $(this).val()).show();
                // Reset results
                DPTotalElement.text('--');
                DPMultiplierElement.text('--');
                DPRawElement.text('--');
            });

            $('#calculate-button').click(function (e) {
                updateUnitStats();
            });

            function updateUnitStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.defense') }}?" + $('#calculate_form').serialize(), {},
                    function(response) {
                        if(response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats data attributes
                                $('#race_'+response.race+' .unit'+slot).data('dp', stats.dp);
                                // Update unit stats display
                                $('#race_'+response.race+' .unit'+slot).text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update OP / DP display
                            DPTotalElement.text(response.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            DPMultiplierElement.text(response.dp_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            DPRawElement.text(response.dp_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
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
            }
        })(jQuery);
    </script>
@endpush
