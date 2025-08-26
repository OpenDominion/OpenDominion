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
                    <td>{{ $race->name }}</td>
                </tr>
                <tr>
                    <td>Land:</td>
                    <td>
                        {{ number_format($data['land']) }}
                        @if (isset($range) && isset($rangeClass))
                            <span class="{{ $rangeClass }}">
                                ({{ round($range, 2) }}%)
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('networth') }}">
                            Networth:
                        </span>
                    </td>
                    <td>{{ number_format($data['networth']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('peasants') }}">
                            Peasants:
                        </span>
                    </td>
                    <td>{{ number_format($data['peasants']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('employment') }}">
                            Employment:
                        </span>
                    </td>
                    <td>{{ number_format($data['employment'], 2) }}%</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('prestige') }}">
                            Prestige:
                        </span>
                    </td>
                    <td>{{ number_format($data['prestige']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('resilience') }}">
                            Resilience:
                        </span>
                    </td>
                    <td>
                        {{ number_format(array_get($data, 'resilience', 0)) }}
                        @if (array_get($data, 'resilience', 0) > 0)
                            <small class="text-muted">
                                ({{ number_format(array_get($data, 'resilience', 0) / 100, 2) }}%)
                            </small>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('spy_mastery') }}">
                            Spy Mastery:
                        </span>
                    </td>
                    <td>{{ number_format(array_get($data, 'spy_mastery', 0)) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('wizard_mastery') }}">
                            Wizard Mastery:
                        </span>
                    </td>
                    <td>{{ number_format(array_get($data, 'wizard_mastery', 0)) }}</td>
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
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('platinum') }}">
                            Platinum:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_platinum']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('food') }}">
                            Food:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_food']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('lumber') }}">
                            Lumber:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_lumber']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('mana') }}">
                            Mana:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_mana']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('ore') }}">
                            Ore:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_ore']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('gems') }}">
                            Gems:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_gems']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('tech') }}">
                            Research Points:
                        </span>
                    </td>
                    <td>{{ number_format($data['resource_tech']) }}</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString('boats') }}">
                            Boats:
                        </span>
                    </td>
                    <td>{{ number_format(rfloor($data['resource_boats'])) }}</td>
                </tr>
                @php $spa = array_get($data, 'spa', -1); @endphp
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('spa') }}">
                            Spy Ratio:
                        </span>
                    </td>
                    <td>{{ $spa == -1 ? '???' : round($spa, 3) }}</td>
                </tr>
                @php $wpa = array_get($data, 'wpa', -1); @endphp
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('wpa') }}">
                            Wizard Ratio:
                        </span>
                    </td>
                    <td>{{ $wpa == -1 ? '???' : round($wpa, 3) }}</td>
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
                        <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString('morale') }}">
                            Morale:
                        </span>
                    </td>
                    <td>{{ number_format($data['morale']) }}%</td>
                </tr>
                <tr>
                    <td>
                        <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $race, true) }}">
                            Draftees:
                        </span>
                    </td>
                    <td>{{ number_format($data['military_draftees']) }}</td>
                </tr>
                @foreach ($unitHelper->getUnitTypes() as $unitType)
                    @php
                        $unit = $race->units->filter(function ($unit) use ($unitType) {
                            return ($unit->slot == (int)str_replace('unit', '', $unitType));
                        })->first();
                    @endphp
                    <tr>
                        <td>
                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $race, true) }}">
                                {{ $unitHelper->getUnitName($unitType, $race) }}:
                            </span>
                        </td>
                        @if (array_key_exists('military_'.$unitType, $data) && $data['military_'.$unitType] !== null)
                            <td>
                                {{ number_format($data['military_'.$unitType]) }}
                            </td>
                        @else
                            <td>
                                ???
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>