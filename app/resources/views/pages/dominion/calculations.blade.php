@extends ('layouts.master')

@section('page-header', 'Calculators')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <form action="" method="get" role="form" id="calculate-defense-form" class="calculate-form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calculator"></i> Defense Calculator</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="race">Race</label>
                            <select name="race" id="race_dp" class="form-control" style="width: 100%;">
                                <option>Select a race</option>
                                @foreach ($races as $race)
                                    <option value="{{ $race->id }}" {{ ($targetDominion !== null && $targetDominion->race_id == $race->id) ? 'selected' : null }}>
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
                                        min="0"
                                        value="{{ $targetDominion !== null ? $landCalculator->getTotalLand($targetDominion) : null }}" />
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
                                        max="100"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "morale") : null }}" />
                            </div>
                        </div>

                        @foreach ($races as $race)
                            <div id="race_{{ $race->id }}_dp" class="table-responsive race_defense_fields" style="display: none;">
                                @php
                                    $buildingFieldsRequired = [];
                                    $landFieldsRequired = [];
                                    $prestigeRequired = false;
                                @endphp
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col>
                                        <col width="10%">
                                        <col width="15%">
                                        <col width="15%">
                                        <col width="15%">
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
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? ceil(array_get($targetInfoOps['clear_sight']->data, "military_draftees") / (array_get($targetInfoOps['clear_sight']->data, "clear_sight_accuracy"))) : null }}" />
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[draftees_home]"
                                                        class="form-control text-center"
                                                        placeholder="--"
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_get($targetInfoOps['barracks_spy']->data, "units.home.draftees") : null }}" />
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
                                                    if (!in_array($building, $buildingFieldsRequired)) {
                                                        $buildingFieldsRequired[] = $building;
                                                    }
                                                }
                                                $landPerks = $unit->perks->where('key', 'defense_from_land');
                                                foreach ($landPerks as $perk) {
                                                    $land = explode(',', $perk->pivot->value)[0];
                                                    if (!in_array($land, $landFieldsRequired)) {
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
                                                            disabled
                                                            value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? ceil(array_get($targetInfoOps['clear_sight']->data, "military_unit{$unit->slot}") / (array_get($targetInfoOps['clear_sight']->data, "clear_sight_accuracy"))) : null }}" />
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}_home]"
                                                            class="form-control text-center"
                                                            placeholder="--"
                                                            min="0"
                                                            disabled
                                                            value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_get($targetInfoOps['barracks_spy']->data, "units.home.unit{$unit->slot}") : null }}" />
                                                </td>
                                                <td class="text-center">
                                                    @if ($unit->power_offense > 0)
                                                        <input type="number"
                                                                name="calc[unit{{ $unit->slot }}_away]"
                                                                class="form-control text-center"
                                                                placeholder="--"
                                                                min="0"
                                                                disabled
                                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_sum(array_get($targetInfoOps['barracks_spy']->data, "units.returning.unit{$unit->slot}", [])) : null }}" />
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
                                                    checked
                                                    disabled />
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
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.{$building}") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 2) : 50 }}" />
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
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('land_spy')) ? round(array_get($targetInfoOps['land_spy']->data, "explored.{$land}.percentage"), 2) : 60 }}" />
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
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "prestige") : null }}" />
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
                                        min="0"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('castle_spy')) ? array_get($targetInfoOps['castle_spy']->data, "walls.rating") * 100 : null }}" />
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
                                        max="20"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.guard_tower") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 2) : null }}" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-9 text-right">
                                &nbsp;
                            </div>
                            <div class="col-xs-3 text-right">
                                <button class="btn btn-primary btn-block" type="button" id="calculate-defense-button">Calculate</button>
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
                            @if ($targetDominion !== null)
                                <tr class="target-dominion-dp">
                                    <td colspan="2"><b>{{ $targetDominion->name }}</b></td>
                                </tr>
                            @endif
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

    <div class="row">
        <div class="col-sm-12 col-md-9">
            <form action="" method="get" role="form" id="calculate-offense-form" class="calculate-form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calculator"></i> Offense Calculator</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="race">Race</label>
                            <select name="race" id="race_op" class="form-control" style="width: 100%;">
                                <option>Select a race</option>
                                @foreach ($races as $race)
                                    <option value="{{ $race->id }}" {{ ($targetDominion !== null && $targetDominion->race_id == $race->id) ? 'selected' : null }}>
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
                                        min="0"
                                        value="{{ $targetDominion !== null ? $landCalculator->getTotalLand($targetDominion) : null }}" />
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
                                        max="100"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "morale") : null }}" />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Prestige
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[prestige]"
                                        class="form-control text-center"
                                        placeholder="250"
                                        min="0"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "prestige") : null }}" />
                            </div>
                        </div>

                        @foreach ($races as $race)
                            <div id="race_{{ $race->id }}_op" class="table-responsive race_offense_fields" style="display: none;">
                                @php
                                    $buildingFieldsRequired = [];
                                    $landFieldsRequired = [];
                                    $wizardRatioRequired = false;
                                    $targetBuildingFieldsRequired = [];
                                    $targetLandRequired = false;
                                    $targetRaceRequired = false;
                                @endphp
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col>
                                        <col width="10%">
                                        <col width="20%">
                                        <col width="20%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>OP</th>
                                            <th class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="Total units from a Clear Sight">
                                                    Accurate
                                                </span>
                                            </th>
                                            <th class="text-center">
                                                <span data-toggle="tooltip" data-placement="top" title="Incoming units from a Barracks Spy">
                                                    Incoming
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <thead>
                                        @foreach ($race->units()->orderBy('slot')->get() as $unit)
                                            @php
                                                $buildingPerks = $unit->perks->where('key', 'offense_from_building');
                                                foreach ($buildingPerks as $perk) {
                                                    $building = explode(',', $perk->pivot->value)[0];
                                                    if (!in_array($building, $buildingFieldsRequired)) {
                                                        $buildingFieldsRequired[] = $building;
                                                    }
                                                }
                                                $landPerks = $unit->perks->where('key', 'offense_from_land');
                                                foreach ($landPerks as $perk) {
                                                    $land = explode(',', $perk->pivot->value)[0];
                                                    if (!in_array($land, $landFieldsRequired)) {
                                                        $landFieldsRequired[] = $land;
                                                    }
                                                }
                                                if ($unit->perks->where('key', 'offense_raw_wizard_ratio')->count()) {
                                                    $wizardRatioRequired = true;
                                                }
                                                $targetBuildingPerks = $unit->perks->where('key', 'offense_vs_building');
                                                foreach ($targetBuildingPerks as $perk) {
                                                    $building = explode(',', $perk->pivot->value)[0];
                                                    if (!in_array($building, $targetBuildingFieldsRequired)) {
                                                        $targetBuildingFieldsRequired[] = $building;
                                                    }
                                                }
                                                if ($unit->perks->where('key', 'offense_staggered_land_range')->count()) {
                                                    $targetLandRequired = true;
                                                }
                                                if ($unit->perks->whereIn('key', ['offense_vs_goblin', 'offense_vs_kobold', 'offense_vs_wood_elf'])->count()) {
                                                    $targetRaceRequired = true;
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
                                                    <span class="op">{{ $unit->power_offense }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}]"
                                                            class="form-control text-center"
                                                            placeholder="0"
                                                            min="0"
                                                            disabled
                                                            {{ $unit->power_offense == 0 ? 'readonly' : null }}
                                                            value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "military_unit{$unit->slot}") : null }}" />
                                                </td>
                                                <td class="text-center">
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}_inc]"
                                                            class="form-control text-center"
                                                            placeholder="--"
                                                            min="0"
                                                            disabled
                                                            {{ $unit->power_offense == 0 ? 'readonly' : null }}
                                                            value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_sum(array_get($targetInfoOps['barracks_spy']->data, "units.training.unit{$unit->slot}", [])) : null }}" />
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
                                        @if (in_array($racialSpell['key'], ['bloodrage', 'crusade', 'howling', 'killing_rage', 'nightfall']))
                                            {{ $racialSpell['name'] }}
                                        @endif
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        @if (in_array($racialSpell['key'], ['bloodrage', 'crusade', 'howling', 'killing_rage', 'nightfall']))
                                            <input type="checkbox"
                                                    step="any"
                                                    name="calc[{{ $racialSpell['key'] }}]"
                                                    checked
                                                    disabled />
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
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.{$building}") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 2) : 50 }}" />
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
                                                    disabled
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('land_spy')) ? round(array_get($targetInfoOps['land_spy']->data, "explored.{$land}.percentage"), 2) : null }}" />
                                        </div>
                                    @endforeach
                                    @if ($wizardRatioRequired)
                                        <div class="col-xs-3 text-right">
                                            Raw Wizard Ratio
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    name="calc[wizard_ratio]"
                                                    class="form-control text-center"
                                                    placeholder="3"
                                                    min="0"
                                                    disabled />
                                        </div>
                                    @endif
                                    @foreach ($targetBuildingFieldsRequired as $building)
                                        <div class="col-xs-3 text-right">
                                            Target {{ ucwords(dominion_attr_display("building_{$building}")) }} %
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    step="any"
                                                    name="calc[target_{{ $building }}_percent]"
                                                    class="form-control text-center"
                                                    placeholder="0"
                                                    min="0"
                                                    disabled />
                                        </div>
                                    @endforeach
                                    @if ($targetLandRequired)
                                        <div class="col-xs-3 text-right">
                                            Target Land
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="number"
                                                    name="calc[target_land]"
                                                    class="form-control text-center"
                                                    placeholder="--"
                                                    min="0"
                                                    disabled />
                                        </div>
                                    @endif
                                    @if ($targetRaceRequired)
                                        <div class="col-xs-3 text-right">
                                            Target Race
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <select name="calc[target_race]" class="form-control" disabled>
                                                <option></option>
                                                @foreach ($races as $targetRace)
                                                    <option value="{{ $targetRace->id }}">
                                                        {{ $targetRace->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Tech
                            </div>
                            <div class="col-xs-3 text-left">
                                <select name="calc[tech_offense]" class="form-control">
                                    <option value="0"></option>
                                    <option value="5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.military_genius")) ? 'selected' : null }}>Military Genius +5%</option>
                                    <option value="10" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.magical_weaponry")) ? 'selected' : null }}>Magical Weaponry +10%</option>
                                </select>
                            </div>
                            <div class="col-xs-3 text-right">
                                Forges %
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[forges_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('castle_spy')) ? array_get($targetInfoOps['castle_spy']->data, "forges.rating") * 100 : null }}" />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                War
                            </div>
                            <div class="col-xs-3 text-left">
                                <select name="calc[war_bonus]" class="form-control">
                                    <option value="0"></option>
                                    <option value="5">Single +5%</option>
                                    <option value="10">Mutual +10%</option>
                                </select>
                            </div>
                            <div class="col-xs-3 text-right">
                                Gryphon Nest %
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        step="any"
                                        name="calc[gryphon_nest_percent]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        max="20"
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.gryphon_nest") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 2) : null }}" />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xs-9 text-right">
                                &nbsp;
                            </div>
                            <div class="col-xs-3 text-right">
                                <button class="btn btn-primary btn-block" type="button" id="calculate-offense-button">Calculate</button>
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
                            @if ($targetDominion !== null)
                                <tr class="target-dominion-op">
                                    <td colspan="2"><b>{{ $targetDominion->name }}</b></td>
                                </tr>
                            @endif
                            <tr style="font-weight: bold;">
                                <td>Total Offense:</td>
                                <td id="op">--</td>
                            </tr>
                            <tr>
                                <td>Offensive Multiplier:</b></td>
                                <td id="op-multiplier">--</td>
                            </tr>
                            <tr>
                                <td>Raw Offense:</td>
                                <td id="op-raw">--</td>
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
        .calculate-form,
        .calculate-form .table>thead>tr>td,
        .calculate-form .table>tbody>tr>td {
            line-height: 2;
        }
        .calculate-form .form-control {
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
            // DEFENSE CALCULATOR
            var DPTotalElement = $('#dp');
            var DPMultiplierElement = $('#dp-multiplier');
            var DPRawElement = $('#dp-raw');

            $('#race_dp').select2().change(function (e) {
                // Hide all racial fields
                $('.race_defense_fields').hide();
                $('.race_defense_fields input').prop('disabled', true);
                $('.race_defense_fields select').prop('disabled', true);
                // Show selected racial fields
                var race_id = $(this).val();
                var race_selector = '#race_' + race_id + '_dp';
                $(race_selector + ' input').prop('disabled', false);
                $(race_selector + ' select').prop('disabled', false);
                $(race_selector).show();
                // Reset results
                DPTotalElement.text('--');
                DPMultiplierElement.text('--');
                DPRawElement.text('--');
            });

            $('#calculate-defense-button').click(function (e) {
                updateUnitDefenseStats();
            });

            function updateUnitDefenseStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.defense') }}?" + $('#calculate-defense-form').serialize(), {},
                    function(response) {
                        if(response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats display
                                $('#race_'+response.race+'_dp .unit'+slot+'_stats span.dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update DP display
                            DPTotalElement.text(response.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            DPMultiplierElement.text(response.dp_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            DPRawElement.text(response.dp_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                        }
                    }
                );
            }

            // OFFENSE CALCULATOR
            var OPTotalElement = $('#op');
            var OPMultiplierElement = $('#op-multiplier');
            var OPRawElement = $('#op-raw');

            $('#race_op').select2().change(function (e) {
                // Hide all racial fields
                $('.race_offense_fields').hide();
                $('.race_offense_fields input').prop('disabled', true);
                $('.race_offense_fields select').prop('disabled', true);
                // Show selected racial fields
                var race_id = $(this).val();
                var race_selector = '#race_' + race_id + '_op';
                $(race_selector + ' input').prop('disabled', false);
                $(race_selector + ' select').prop('disabled', false);
                $(race_selector).show();
                // Reset results
                OPTotalElement.text('--');
                OPMultiplierElement.text('--');
                OPRawElement.text('--');
            });

            $('#calculate-offense-button').click(function (e) {
                updateUnitOffenseStats();
            });

            function updateUnitOffenseStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.offense') }}?" + $('#calculate-offense-form').serialize(), {},
                    function(response) {
                        if(response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats display
                                $('#race_'+response.race+'_op .unit'+slot+'_stats span.op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update DP display
                            OPTotalElement.text(response.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            OPMultiplierElement.text(response.op_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            OPRawElement.text(response.op_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                        }
                    }
                );
            }

            @if ($targetDominion !== null)
                $('#race_dp').trigger('change');
                //$('#calculate-defense-button').trigger('click');
                $('#race_dp').select2().change(function (e) {
                    $('.target-dominion-dp').hide();
                });
                $('#race_op').trigger('change');
                //$('#calculate-offense-button').trigger('click');
                $('#race_op').select2().change(function (e) {
                    $('.target-dominion-op').hide();
                });
            @endif
        })(jQuery);
    </script>
@endpush
