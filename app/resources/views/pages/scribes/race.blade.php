@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $race->name }}</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12 col-md-9">
                    {{-- Description --}}
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Description</h4>
                    <em>
                        {!! $race->description !!}
                    </em>

                    <div class="row">
                        <div class="col-md-12 col-md-3">
                            {{-- Home land --}}
                            <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Home land</h4>
                            <p>
                                {!! $landHelper->getLandTypeIconHtml($race->home_land_type) !!} {{ ucfirst($race->home_land_type) }}
                            </p>
                        </div>
                        <div class="col-md-12 col-md-9">
                            {{-- Racial Spell --}}
                            <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Racial Spell</h4>
                            @php
                                $racialSpell = $spellHelper->getRacialSelfSpell($race);
                            @endphp
                            <p>
                                @if ($racialSpell)
                                    <strong>{{ $racialSpell['name'] }}</strong>: {{ $racialSpell['description'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-md-3">
                    <table class="table table-striped">
                        <colgroup>
                            <col>
                            <col width="50px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Race Perk</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($race->perks as $perk)
                                @php
                                    $perkDescription = $raceHelper->getPerkDescriptionHtmlWithValue($perk);
                                @endphp
                                <tr>
                                    <td>
                                        {!! $perkDescription['description'] !!}
                                    </td>
                                    <td class="text-center">
                                        {!! $perkDescription['value']  !!}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    {{-- Military Units --}}
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Military Units</h4>

                    <table class="table table-striped">
                        <colgroup>
                            <col width="200px">
                            <col width="50px">
                            <col width="50px">
                            <col>
                            <col width="100px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th class="text-center">OP</th>
                                <th class="text-center">DP</th>
                                <th>Perks</th>
                                <th class="text-center">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($race->units as $unit)
                                @php
                                    $unitCostString = (number_format($unit->cost_platinum) . 'p');

                                    if ($unit->cost_ore > 0) {
                                        $unitCostString .= (', ' . number_format($unit->cost_ore) . 'r');
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        {!! $unitHelper->getUnitTypeIconHtml("unit{$unit->slot}", $race) !!}
                                        {{ $unit->name }}
                                    </td>
                                    <td class="text-center">
                                        {{ $unit->power_offense }}
                                    </td>
                                    <td class="text-center">
                                        {{ $unit->power_defense }}
                                    </td>
                                    <td>
                                        {!! $unitHelper->getUnitHelpString("unit{$unit->slot}", $race) !!}
                                    </td>
                                    <td class="text-center">
                                        {{ $unitCostString }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
