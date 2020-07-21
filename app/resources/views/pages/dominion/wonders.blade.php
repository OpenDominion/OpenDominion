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
                            <col width="100">
                            <col width="100">
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
                            @foreach ($wonders as $wonder)
                                <tr>
                                    <td>
                                        {{ $wonder->wonder->name }}
                                    </td>
                                    <td>
                                        @if ($wonder->realm)
                                            #{{ $wonder->realm->number }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($wonder->power) }}
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

            <form action="{{ route('dominion.wonders') }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Attack</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <label for="target_wonder">Select a target</label>
                            <select name="target_wonder" id="target_wonder" class="form-control select2" required style="width: 100%" data-placeholder="Select a target wonder" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                <option></option>
                                @foreach ($wonders as $wonder)
                                    @if ($wonder->realm == null || $selectedDominion->realm->war_realm_id == $wonder->realm->id || $selectedDominion->realm_id == $wonder->realm->war_realm_id)
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
                        <div class="pull-right">
                            <button type="submit"
                                    name="action"
                                    value="spell"
                                    class="btn btn-primary"
                                    {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() ? 'disabled' : null }}>
                                <i class="ra ra-lightning-storm"></i>
                                Lightning Bolt
                            </button>
                            <div class="small text-center">
                                Mana cost: 2,839
                            </div>
                        </div>
                        <div class="clearfix"></div>
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
                    <div class="box-footer">
                        <button type="submit"
                                name="action"
                                value="attack"
                                id="attack-button"
                                class="btn btn-danger"
                                {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() ? 'disabled' : null }}>
                            <i class="ra ra-crossed-swords"></i>
                            Attack
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Wonders provide bonuses to all dominions in the controlling realm and are acquired by destroying and rebuilding them.</p>
                    <p>All wonders will begin each round in realm 0, with a starting power of 250,000. Once rebuilt, wonder power depends on the damage your realm did to it and time into the round.</p>
                    <p>Each dominion in a realm destroying a wonder that is not in realm 0 receives 100 prestige.</p>
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
            $('#target_wonder').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
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
