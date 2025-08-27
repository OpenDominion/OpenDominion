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
                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Race
                            </div>
                            <div class="col-xs-3">
                                <input type="hidden" name="race" value="{{ $race->id }}" />
                                <input class="form-control text-center" value="{{ $race->name }}" readonly />
                            </div>
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
                        </div>
                        <div class="form-group row">
                            <div class="col-xs-3">&nbsp;</div>
                            <div class="col-xs-3">&nbsp;</div>
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

                        <div id="defense" class="table-responsive">
                            @php
                                $buildingFieldsRequired = [];
                                $landFieldsRequired = [];
                                $prestigeRequired = false;
                                $clearSightAccuracy = 1;
                                if ($targetDominion !== null && $targetInfoOps->has('clear_sight')) {
                                    $clearSightAccuracy = array_get($targetInfoOps['clear_sight']->data, "clear_sight_accuracy");
                                    if ($clearSightAccuracy == null || $clearSightAccuracy == 0) {
                                        $clearSightAccuracy = 1;
                                    }
                                }
                            @endphp
                            <table class="table table-condensed" style="margin-bottom: 0px;">
                                <colgroup>
                                    <col>
                                    <col width="10%">
                                    <col width="3%">
                                    <col width="15%">
                                    <col width="15%">
                                    <col width="15%">
                                    <col width="15%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>DP</th>
                                        <th></th>
                                        <th class="text-center">
                                            <span data-toggle="tooltip" data-placement="top" title="Total units from a Clear Sight">
                                                Accurate
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span data-toggle="tooltip" data-placement="top" title="Estimated units home from a Barracks Spy<br><br>Ignored if accurate count is provided without also providing an away count">
                                                Home
                                            </span>
                                        </th>
                                        <th class="text-center">
                                            <span data-toggle="tooltip" data-placement="top" title="Estimated units away from a Barracks Spy<br><br>Ignored if accurate count is not provided">
                                                Away
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
                                    <tr>
                                        <td>
                                            Draftees
                                        </td>
                                        <td>
                                            1
                                        </td>
                                        <td>
                                            <input type="checkbox" id="dp-military-draftee" style="margin-top: 8px;" checked />
                                        </td>
                                        <td class="text-center">
                                            <input type="number"
                                                    name="calc[draftees]"
                                                    class="form-control text-center dp-military-draftee"
                                                    data-unit-disabled="0"
                                                    placeholder="0"
                                                    min="0"
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? rceil(array_get($targetInfoOps['clear_sight']->data, "military_draftees") / $clearSightAccuracy) : null }}" />
                                        </td>
                                        <td class="text-center">
                                            <input type="number"
                                                    name="calc[draftees_home]"
                                                    class="form-control text-center dp-military-draftee"
                                                    data-unit-disabled="0"
                                                    placeholder="--"
                                                    min="0"
                                                    value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_get($targetInfoOps['barracks_spy']->data, "units.home.draftees") : null }}" />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control text-center" placeholder="--" readonly disabled />
                                        </td>
                                        <td>
                                            <input type="number" class="form-control text-center" placeholder="--" readonly disabled />
                                        </td>
                                    </tr>
                                    @foreach ($race->units->sortBy('slot') as $unit)
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
                                            <td>
                                                <input type="checkbox" id="dp-military-unit{{ $unit->slot }}" style="margin-top: 8px;" checked />
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[unit{{ $unit->slot }}]"
                                                        class="form-control text-center dp-military-unit{{ $unit->slot }}"
                                                        data-unit-disabled="0"
                                                        placeholder="0"
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? rceil(array_get($targetInfoOps['clear_sight']->data, "military_unit{$unit->slot}") / $clearSightAccuracy) : null }}" />
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[unit{{ $unit->slot }}_home]"
                                                        class="form-control text-center dp-military-unit{{ $unit->slot }}"
                                                        data-unit-disabled="0"
                                                        placeholder="--"
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_get($targetInfoOps['barracks_spy']->data, "units.home.unit{$unit->slot}") : null }}" />
                                            </td>
                                            <td class="text-center">
                                                @if ($unit->power_offense > 0 || $unit->perks->where('key', 'rebirth')->isNotEmpty())
                                                    <input type="number"
                                                            name="calc[unit{{ $unit->slot }}_away]"
                                                            class="form-control text-center dp-military-unit{{ $unit->slot }}"
                                                            data-unit-disabled="0"
                                                            placeholder="--"
                                                            min="0"
                                                            value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_sum(array_get($targetInfoOps['barracks_spy']->data, "units.returning.unit{$unit->slot}", [])) : null }}" />
                                                @else
                                                    <input type="number" class="form-control text-center" placeholder="--" readonly disabled />
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[unit{{ $unit->slot }}_inc]"
                                                        class="form-control text-center dp-military-unit{{ $unit->slot }} dp-inc"
                                                        data-unit-disabled="0"
                                                        data-inc-disabled="1"
                                                        placeholder="--"
                                                        min="0"
                                                        disabled
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_sum(array_get($targetInfoOps['barracks_spy']->data, "units.training.unit{$unit->slot}", [])) : null }}" />
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td colspan="2">
                                            <div class="checkbox text-center" style="margin: 0px;">
                                                <label>
                                                    <input type="checkbox" name="calc[accurate]" style="margin-top: 8px;" {{ ($targetDominion !== null && $targetDominion->realm_id == $selectedDominion->realm_id) ? 'checked' : null }} />
                                                    <span data-toggle="tooltip" data-placement="top" title="The unit counts entered into the Home/Away fields should be treated as 100% accurate">
                                                        Use exact unit counts
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="checkbox text-center" style="margin: 0px;">
                                                <label>
                                                    <input type="checkbox" id="dp-inc" style="margin-top: 8px;" />
                                                    <span data-toggle="tooltip" data-placement="top" title="Calculate defense including units in training">
                                                        Include
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                </thead>
                            </table>

                            <div class="form-group row">
                                @php
                                    $racialSpell = $spellHelper->getSpellsWithPerk('defense', $race)->first();
                                @endphp
                                <div class="col-xs-3 text-right">
                                    @if ($racialSpell)
                                        {{ $racialSpell->name }}
                                    @endif
                                </div>
                                <div class="col-xs-3 text-left">
                                    @if ($racialSpell)
                                        <input type="checkbox"
                                                name="calc[{{ $racialSpell->key }}]"
                                                checked />
                                    @endif
                                </div>
                                @foreach ($buildingFieldsRequired as $building)
                                    <div class="col-xs-3 text-right">
                                        {{ $buildingHelper->getBuildingName($building) }} %
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                step="any"
                                                name="calc[{{ $building }}_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.{$building}") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 6) : 50 }}" />
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
                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('land_spy')) ? round(array_get($targetInfoOps['land_spy']->data, "explored.{$land}.percentage"), 6) : 60 }}" />
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
                                                value="{{ ($targetDominion !== null && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "prestige") : null }}" />
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Ares Call
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="checkbox"
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
                                <div class="input-group">
                                    <input type="number"
                                            step="any"
                                            name="calc[temple_percent]"
                                            class="form-control text-center"
                                            placeholder="0"
                                            min="0"
                                            max="16.67" />
                                    <span class="input-group-btn">
                                        <button class="btn btn-sm btn-primary load-temples"
                                                data-temples-attacker="{{ round($selectedDominion->building_temple / $landCalculator->getTotalLand($selectedDominion) * 100, 6) }}"
                                                data-temple-of-the-damned="{{ $selectedDominion->realm->hasWonder('temple_of_the_damned') }}"
                                                type="button">
                                            Load
                                        </button>
                                    </span>
                                </div>
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
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.guard_tower") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 6) : null }}" />
                            </div>
                        </div>

                        @if (isset($wonders['temple_of_the_damned']))
                            <div class="form-group row">
                                @php $attackerPerk = $wonders['temple_of_the_damned']->perks->where('key', 'enemy_defense')->first(); @endphp
                                <div class="col-xs-3 text-right">
                                    Temple of the Damned (Attacker)
                                </div>
                                <div class="col-xs-3 text-left">
                                    <input type="hidden"
                                            name="calc[wonder_enemy_defense]"
                                            value="{{ $attackerPerk->pivot->value }}" />
                                    <input type="checkbox"
                                            name="calc[temple_of_the_damned_attacker]" />
                                </div>
                                @php $targetPerk = $wonders['temple_of_the_damned']->perks->where('key', 'defense')->first(); @endphp
                                <div class="col-xs-3 text-right">
                                    Temple of the Damned (Target)
                                </div>
                                <div class="col-xs-3 text-left">
                                    <input type="hidden"
                                            name="calc[wonder_defense]"
                                            value="{{ $targetPerk->pivot->value }}" />
                                    <input type="checkbox"
                                            name="calc[temple_of_the_damned_defender]"
                                            {{ $targetDominion->realm->hasWonder('temple_of_the_damned') ? 'checked' : null }} />
                                </div>
                            </div>
                        @endif

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
                    @if ($targetDominion->realm_id !== $selectedDominion->realm_id)
                        <div class="box-tools pull-right">
                            <a href="{{ route('dominion.invade') }}?dominion={{ $targetDominion->id }}"
                                id="invade-button"
                                class="btn btn-danger" style="font-size: 18px; padding: 2px 4px 0px;"
                                title="Invade" data-toggle="tooltip">
                                <i class="ra ra-crossed-swords"></i>
                            </a>
                        </div>
                    @endif
                </div>
                <div class="box-body table-responsive">
                    <table class="table">
                        @if ($targetDominion !== null)
                            <thead>
                                <tr class="target-dominion-dp" style="overflow-wrap: anywhere;">
                                    <td colspan="2"><b>{{ $targetDominion->name }} (#{{ $targetDominion->realm->number }})</b></td>
                                </tr>
                            </thead>
                        @endif
                        <tbody>
                            <tr style="font-weight: bold;">
                                <td>Total Defense:</td>
                                <td id="dp">--</td>
                            </tr>
                            <tr>
                                <td>vs Temples:</td>
                                <td id="dp-temples">--</td>
                            </tr>
                            <tr>
                                <td>Defensive Multiplier:</td>
                                <td id="dp-multiplier">--</td>
                            </tr>
                            <tr>
                                <td>Raw Defense:</td>
                                <td id="dp-raw">--</td>
                            </tr>
                            @if ($targetDominion !== null)
                                <tr class="target-dominion-dp">
                                    <td>Current Land:</td>
                                    <td>{{ $landCalculator->getTotalLand($targetDominion) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($targetDominion !== null && $selectedDominion->realm_id !== $targetDominion->realm_id)
                <div class="box target-dominion-dp">
                    <div class="box-header with-border">
                        <h3 class="box-title">Info Ops</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <td colspan="2"><b>{{ $targetDominion->name }} (#{{ $targetDominion->realm->number }})</b></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Clear Sight:</td>
                                    <td>
                                        @if ($targetInfoOps->has('clear_sight'))
                                            @if ($targetInfoOps['clear_sight']->isInvalid())
                                                <span class="label label-danger">Invalid</span>
                                            @elseif ($targetInfoOps['clear_sight']->isStale())
                                                <span class="label label-warning">Stale</span>
                                            @else
                                                <span class="label label-success">Current</span>
                                            @endif
                                        @else
                                            <span class="label label-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Barracks Spy:</td>
                                    <td>
                                        @if ($targetInfoOps->has('barracks_spy'))
                                            @if ($targetInfoOps['barracks_spy']->isInvalid())
                                                <span class="label label-danger">Invalid</span>
                                            @elseif ($targetInfoOps['barracks_spy']->isStale())
                                                <span class="label label-warning">Stale</span>
                                            @else
                                                <span class="label label-success">Current</span>
                                            @endif
                                        @else
                                            <span class="label label-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Castle Spy:</td>
                                    <td>
                                        @if ($targetInfoOps->has('castle_spy'))
                                            @if ($targetInfoOps['castle_spy']->isInvalid())
                                                <span class="label label-danger">Invalid</span>
                                            @elseif ($targetInfoOps['castle_spy']->isStale())
                                                <span class="label label-warning">Stale</span>
                                            @else
                                                <span class="label label-success">Current</span>
                                            @endif
                                        @else
                                            <span class="label label-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Survey Dominion:</td>
                                    <td>
                                        @if ($targetInfoOps->has('survey_dominion'))
                                            @if ($targetInfoOps['survey_dominion']->isInvalid())
                                                <span class="label label-danger">Invalid</span>
                                            @elseif ($targetInfoOps['survey_dominion']->isStale())
                                                <span class="label label-warning">Stale</span>
                                            @else
                                                <span class="label label-success">Current</span>
                                            @endif
                                        @else
                                            <span class="label label-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Land Spy:</td>
                                    <td>
                                        @if ($targetInfoOps->has('land_spy'))
                                            @if ($targetInfoOps['land_spy']->isInvalid())
                                                <span class="label label-danger">Invalid</span>
                                            @elseif ($targetInfoOps['land_spy']->isStale())
                                                <span class="label label-warning">Stale</span>
                                            @else
                                                <span class="label label-success">Current</span>
                                            @endif
                                        @else
                                            <span class="label label-danger">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
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
                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Race
                            </div>
                            <div class="col-xs-3">
                                <input type="hidden" name="race" value="{{ $race->id }}" />
                                <input class="form-control text-center" value="{{ $race->name }}" readonly />
                            </div>
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

                        <div id="offense" class="table-responsive">
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
                                    <col width="3%">
                                    <col width="20%">
                                    <col width="20%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>OP</th>
                                        <th></th>
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
                                    @foreach ($race->units->sortBy('slot') as $unit)
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
                                            <td>
                                                <input type="checkbox" id="op-military-unit{{ $unit->slot }}" style="margin-top: 8px;" checked />
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[unit{{ $unit->slot }}]"
                                                        class="form-control text-center op-military-unit{{ $unit->slot }}"
                                                        data-unit-disabled="0"
                                                        placeholder="0"
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? array_get($targetInfoOps['clear_sight']->data, "military_unit{$unit->slot}") : null }}" />
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                        name="calc[unit{{ $unit->slot }}_inc]"
                                                        class="form-control text-center op-military-unit{{ $unit->slot }} op-inc"
                                                        data-unit-disabled="0"
                                                        data-inc-disabled="0"
                                                        placeholder="--"
                                                        min="0"
                                                        value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('barracks_spy')) ? array_sum(array_get($targetInfoOps['barracks_spy']->data, "units.training.unit{$unit->slot}", [])) : null }}" />
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <div class="checkbox text-center" style="margin: 0px;">
                                                <label>
                                                    <input type="checkbox" id="op-inc" style="margin-top: 8px;" checked />
                                                    <span data-toggle="tooltip" data-placement="top" title="Calculate offense including units in training">
                                                        Include
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                </thead>
                            </table>

                            <div class="form-group row">
                                @php
                                    $racialSpell = $spellHelper->getSpellsWithPerk(['offense', 'offense_from_barren_land', 'offense_from_spell', 'offense_unit1'], $race)->first();
                                @endphp
                                <div class="col-xs-3 text-right">
                                    @if ($racialSpell)
                                        {{ $racialSpell->name }}
                                    @endif
                                </div>
                                <div class="col-xs-3 text-left">
                                    @if ($racialSpell)
                                        <input type="checkbox"
                                                name="calc[{{ $racialSpell->key }}]"
                                                checked />
                                    @endif
                                </div>
                                @if ($race->key == 'nomad-rework')
                                    <div class="col-xs-3 text-right">
                                        Barren Land %
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                step="any"
                                                name="calc[barren_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "barren_land") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 6) : 50 }}" />
                                    </div>
                                @endif
                                @foreach ($buildingFieldsRequired as $building)
                                    <div class="col-xs-3 text-right">
                                        {{ $buildingHelper->getBuildingName($building) }} %
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                step="any"
                                                name="calc[{{ $building }}_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.{$building}") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 6) : 50 }}" />
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
                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('land_spy')) ? round(array_get($targetInfoOps['land_spy']->data, "explored.{$land}.percentage"), 6) : null }}" />
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
                                                value="{{ ($targetDominion !== null && $targetDominion->race_id == $race->id && $targetInfoOps->has('clear_sight')) ? round(array_get($targetInfoOps['clear_sight']->data, "wpa", 3), 3) : null }}" />
                                    </div>
                                @endif
                                @foreach ($targetBuildingFieldsRequired as $building)
                                    <div class="col-xs-3 text-right">
                                        Target {{ $buildingHelper->getBuildingName($building) }} %
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                step="any"
                                                name="calc[target_{{ $building }}_percent]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0" />
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
                                                min="0" />
                                    </div>
                                @endif
                                @if ($targetRaceRequired)
                                    <div class="col-xs-3 text-right">
                                        Target Race
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <select name="calc[target_race]" class="form-control">
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

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Tech
                            </div>
                            <div class="col-xs-3 text-left">
                                <select name="calc[tech_offense]" class="form-control">
                                    <option value="0"></option>
                                    <option value="2.5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.tech_13_13")) ? 'selected' : null }}>Ares' Favor +2.5%</option>
                                    <option value="5" {{ ($targetDominion !== null && $targetInfoOps->has('vision') && array_get($targetInfoOps['vision']->data, "techs.tech_11_9")) ? 'selected' : null }}>Avatar of Ares +5%</option>
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
                                    <option value="4">Single +4%</option>
                                    <option value="8">Mutual +8%</option>
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
                                        value="{{ ($targetDominion !== null && $targetInfoOps->has('survey_dominion')) ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.gryphon_nest") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 6) : null }}" />
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
                        @if ($targetDominion !== null)
                            <thead>
                                <tr class="target-dominion-op" style="overflow-wrap: anywhere;">
                                    <td colspan="2"><b>{{ $targetDominion->name }} (#{{ $targetDominion->realm->number }})</b></td>
                                </tr>
                            </thead>
                        @endif
                        <tbody>
                            <tr style="font-weight: bold;">
                                <td>Total Offense:</td>
                                <td id="op">--</td>
                            </tr>
                            <tr>
                                <td>Temples:</td>
                                <td id="op-temples">
                                  {{ $targetInfoOps->has('survey_dominion') ? round(array_get($targetInfoOps['survey_dominion']->data, "constructed.temple") / array_get($targetInfoOps['survey_dominion']->data, "total_land") * 100, 3) : 0 }}%
                                </td>
                            </tr>
                            <tr>
                                <td>Offensive Multiplier:</td>
                                <td id="op-multiplier">--</td>
                            </tr>
                            <tr>
                                <td>Raw Offense:</td>
                                <td id="op-raw">--</td>
                            </tr>
                            @if ($targetDominion !== null)
                                <tr class="target-dominion-op">
                                    <td>Current Land:</td>
                                    <td>{{ $landCalculator->getTotalLand($targetDominion) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The calculator can be pre-filled with data from the ops center.</p>
                    <p>Values will be taken from their corresponding op. Land spy is needed to get a pre-filled land percentage for races with units that depend on it.</p>
                    <p>Old ops (except for Barracks Spy) will be pre-filled.</p>
                    <p>You are responsible for validating that the values are correct.</p>
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
            var DPTemplesElement = $('#dp-temples');
            var DPMultiplierElement = $('#dp-multiplier');
            var DPRawElement = $('#dp-raw');

            $('#calculate-defense-button').click(function (e) {
                updateUnitDefenseStats();
            });

            function updateUnitDefenseStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.defense') }}?" + $('#calculate-defense-form').serialize(), {},
                    function(response) {
                        if (response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats display
                                $('#defense .unit'+slot+'_stats span.dp').text(stats.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update DP display
                            DPTotalElement.text(response.dp.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            DPTemplesElement.text(($('input[name=calc\\[temple_percent\\]]').val() || 0) + '%');
                            DPMultiplierElement.text(response.dp_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            DPRawElement.text(response.dp_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));

                            // Update invade button URL with DP value
                            var invadeButton = $('#invade-button');
                            if (invadeButton.length) {
                                var currentHref = invadeButton.attr('href');
                                var baseUrl = currentHref.split('&dp=')[0];
                                var newHref = baseUrl + '&dp=' + Math.ceil(response.dp);
                                invadeButton.attr('href', newHref);
                            }
                        }
                    }
                );
            }

            function disableDefenseInputs() {
                $('input[class*=dp-]').attr("disabled", false);
                $('input[class*=dp-][data-inc-disabled=1]').attr("disabled", true);
                $('input[class*=dp-][data-unit-disabled=1]').attr("disabled", true);
            }

            $('input[id^=dp-military-]').change(function (e) {
                if (this.checked) {
                    $('.'+this.id).attr('data-unit-disabled', 0);
                } else {
                    $('.'+this.id).attr('data-unit-disabled', 1);
                }
                disableDefenseInputs();
            });

            $('input[id=dp-inc]').change(function (e) {
                if (this.checked) {
                    $('.'+this.id).attr('data-inc-disabled', 0);
                } else {
                    $('.'+this.id).attr('data-inc-disabled', 1);
                }
                disableDefenseInputs();
            });

            function disableOffenseInputs() {
                $('input[class*=op-]').attr("disabled", false);
                $('input[class*=op-][data-inc-disabled=1]').attr("disabled", true);
                $('input[class*=op-][data-unit-disabled=1]').attr("disabled", true);
            }

            $('input[id^=op-military-]').change(function (e) {
                if (this.checked) {
                    $('.'+this.id).attr('data-unit-disabled', 0);
                } else {
                    $('.'+this.id).attr('data-unit-disabled', 1);
                }
                disableOffenseInputs();
            });

            $('input[id=op-inc]').change(function (e) {
                if (this.checked) {
                    $('.'+this.id).attr('data-inc-disabled', 0);
                } else {
                    $('.'+this.id).attr('data-inc-disabled', 1);
                }
                disableOffenseInputs();
            });

            // Trigger field updates after pushState
            setTimeout(function() {
                $('input[id^=dp-military-]').trigger('change');
                $('input[id=dp-inc]').trigger('change');
                $('input[id^=op-military-]').trigger('change');
                $('input[id=op-inc]').trigger('change');
            }, 100);

            // OFFENSE CALCULATOR
            var OPTotalElement = $('#op');
            var OPMultiplierElement = $('#op-multiplier');
            var OPRawElement = $('#op-raw');

            $('#calculate-offense-button').click(function (e) {
                updateUnitOffenseStats();
            });

            function updateUnitOffenseStats() {
                // Update unit stats
                $.get(
                    "{{ route('api.calculator.offense') }}?" + $('#calculate-offense-form').serialize(), {},
                    function(response) {
                        if (response.result == 'success') {
                            $.each(response.units, function(slot, stats) {
                                // Update unit stats display
                                $('#offense .unit'+slot+'_stats span.op').text(stats.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            });
                            // Update DP display
                            OPTotalElement.text(response.op.toLocaleString(undefined, {maximumFractionDigits: 2}));
                            OPMultiplierElement.text(response.op_multiplier.toLocaleString(undefined, {maximumFractionDigits: 2}) + '%');
                            OPRawElement.text(response.op_raw.toLocaleString(undefined, {maximumFractionDigits: 2}));
                        }
                    }
                );
            }

            $('.load-temples').click(function(e) {
                var temples = $(this).data('temples-attacker');
                $('input[name=calc\\[temple_percent\\]]').val(temples);
                var templeOfTheDamned = $(this).data('temple-of-the-damned');
                $('input[name=calc\\[temple_of_the_damned_attacker\\]]').prop('checked', templeOfTheDamned);
            });
        })(jQuery);
    </script>
@endpush
