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
                    <div class="box-body">
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

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Land
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[land]"
                                        class="form-control text-center"
                                        placeholder="250"
                                        min="0" />
                            </div>
                            <div class="col-xs-3 text-right">
                                Morale
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[morale]"
                                        class="form-control text-center"
                                        placeholder="100"
                                        min="0"
                                        max="100" />
                            </div>
                        </div>

                        @foreach ($races as $race)
                            <div id="race_{{ $race->id }}" class="race_units table-responsive" style="display: none;">
                                @php
                                    $buildingFieldsRequired = [];
                                    $landFieldsRequired = [];
                                    $prestigeRequired = false;
                                @endphp
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col>
                                        <col width="100">
                                        <col width="150">
                                        <col width="150">
                                        <col width="150">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>DP</th>
                                            <th class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="Total units from a Clear Sight">
                                                    Accurate
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="Estimated units home from a Barracks Spy<br><br>Ignored if accurate count is provided without also providing an away count.">
                                                    Home
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="Estimated units away from a Barracks Spy<br><br>Ignored if accurate count is not provided.">
                                                    Away
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <thead>
                                        <tr>
                                            <td>
                                                Draftees
                                            </td>
                                            <td>
                                                1
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[draftees]"
                                                        class="form-control text-center"
                                                        placeholder="0"
                                                        min="0" />
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[draftees_home]"
                                                        class="form-control text-center"
                                                        placeholder="--"
                                                        min="0" />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control text-center" placeholder="--" readonly disabled />
                                            </td>
                                        </tr>
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
                                                <td class="unit{{ $unit->slot }}_stats">
                                                    <span class="dp">{{ $unit->power_defense }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}]"
                                                            class="form-control text-center"
                                                            placeholder="0"
                                                            min="0"
                                                            disabled />
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}_home]"
                                                            class="form-control text-center"
                                                            placeholder="--"
                                                            min="0"
                                                            disabled />
                                                </td>
                                                <td class="text-center">
                                                    @if ($unit->power_offense > 0)
                                                        <input type="number"
                                                                name="calc[unit{{ $unit->slot }}_away]"
                                                                class="form-control text-center"
                                                                placeholder="--"
                                                                min="0"
                                                                disabled />
                                                    @else
                                                        <input type="number" class="form-control text-center" placeholder="--" readonly disabled />
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </thead>
                                </table>

                                <div class="form-group row">
                                    @php
                                        $racialSpell = $spellHelper->getRacialSelfSpell($race);
                                    @endphp
                                    <div class="col-xs-3 text-right">
                                        @if (in_array($racialSpell['key'], ['blizzard', 'defensive_frenzy', 'howling']))
                                            {{ $racialSpell['name'] }}
                                        @endif
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        @if (in_array($racialSpell['key'], ['blizzard', 'defensive_frenzy', 'howling']))
                                            <input type="checkbox"
                                                    step="any"
                                                    name="calc[{{ $racialSpell['key'] }}]"
                                                    id="{{ $racialSpell['key'] }}"
                                                    checked />
                                        @endif
                                    </div>
                                    @foreach ($buildingFieldsRequired as $building)
                                        <div class="col-xs-3 text-right">
                                            {{ ucwords(dominion_attr_display("building_{$building}")) }} %
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    step="any"
                                                    name="calc[{{ $building }}_percent]"
                                                    class="form-control text-center"
                                                    placeholder="0"
                                                    min="0"
                                                    value="50" />
                                        </div>
                                    @endforeach
                                    @foreach ($landFieldsRequired as $land)
                                        <div class="col-xs-3 text-right">
                                            {{ ucwords(dominion_attr_display("land_{$land}")) }} %
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    step="any"
                                                    name="calc[{{ $land }}_percent]"
                                                    class="form-control text-center"
                                                    placeholder="0"
                                                    min="0"
                                                    value="60" />
                                        </div>
                                    @endforeach
                                    @if ($prestigeRequired)
                                        <div class="col-xs-3 text-right">
                                            Prestige
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    name="calc[prestige]"
                                                    class="form-control text-center"
                                                    placeholder="250"
                                                    min="0"
                                                    max="250" />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                    Ares Call
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="checkbox"
                                        step="any"
                                        name="calc[ares_call]"
                                        id="ares_call"
                                        checked />
                            </div>
                            <div class="col-xs-3 text-right">
                                Walls %
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[walls_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0" />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Attacker's Temples %
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[temple_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        max="16.67" />
                            </div>
                            <div class="col-xs-3 text-right">
                                Guard Tower %
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[guard_tower_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        max="20" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-9 text-right">
                                &nbsp;
                            </div>
                            <div class="col-xs-3 text-right">
                                <button class="btn btn-primary btn-block" type="button" id="calculate-button">Calculate</button>
                            </div>
                        </div>
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

@push('inline-styles')
    <style type="text/css">
        #calculate_form,
        #calculate_form .table>thead>tr>td,
        #calculate_form .table>tbody>tr>td {
            line-height: 2;
        }
        #calculate_form .form-control {
            height: 30px;
            padding: 3px 6px;
        }
    </style>
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
                                // Update unit stats display
                                $('#race_'+response.race+' .unit'+slot+'_stats span.dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update OP / DP display
                            DPTotalElement.text(response.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            DPMultiplierElement.text(response.dp_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            DPRawElement.text(response.dp_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            //calculate();
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
