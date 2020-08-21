<div class="row">
    <div class="col-xs-12 col-sm-4">
        <table class="table">
            <colgroup>
                <col width="50%">
                <col width="50%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2">Overview</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ruler:</td>
                    <td>{{ $data['ruler_name'] }}</td>
                </tr>
                <tr>
                    <td>Race:</td>
                    <td>{{ $data['race']->name }}</td>
                </tr>
                <tr>
                    <td>Land:</td>
                    <td>
                        {{ number_format($data['land']) }}
                        @if($data['range'])
                            <span class="{{ $data['range_class'] }}">
                                ({{ $data['range'] }}%)
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("peasants") }}">
                            Peasants:
                        </span>
                    </td>
                    <td>{{ number_format($data['peasants']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("employment") }}">
                            Employment:
                        </span>
                    </td>
                    <td>{{ number_format($data['employment'], 2) }}%</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("networth") }}">
                            Networth:
                        </span>
                    </td>
                    <td>{{ number_format($data['networth']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("prestige") }}">
                            Prestige:
                        </span>
                    </td>
                    <td>{{ number_format($data['prestige']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-xs-12 col-sm-4">
        <table class="table">
            <colgroup>
                <col width="50%">
                <col width="50%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2">Resources</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("platinum") }}">
                            Platinum:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_platinum']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("food") }}">
                            Food:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_food']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("lumber") }}">
                            Lumber:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_lumber']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("mana") }}">
                            Mana:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_mana']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("ore") }}">
                            Ore:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_ore']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("gems") }}">
                            Gems:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_gems']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("tech") }}">
                            Research Points:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_tech']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("boats") }}">
                            Boats:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_boats']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-xs-12 col-sm-4">
        <table class="table">
            <colgroup>
                <col width="50%">
                <col width="50%">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2">Military</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("morale") }}">
                            Morale:
                        </span>
                    </td>
                    <td>{{ number_format($data['morale']) }}%</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $dominion->race, true) }}">
                            Draftees:
                        </span>
                    </td>
                    <td>{{ number_format($data['military_draftees']) }}</td>
                </tr>
                @foreach ($unitHelper->getUnitTypes() as $unitType)
                    @php
                        $unit = $data['race']->units->filter(function ($unit) use ($unitType) {
                            return ($unit->slot == (int)str_replace('unit', '', $unitType));
                        })->first();
                    @endphp
                    <tr>
                        <td>
                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $dominion->race, true) }}">
                                {{ $unitHelper->getUnitName($unitType, $dominion->race) }}:
                            </span>
                        </td>
                        @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
                            <td>
                                {{ number_format($data["military_unit$unit->slot"]) }}
                            </td>
                        @else
                            <td>
                                {{ $data[$unitType] }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>