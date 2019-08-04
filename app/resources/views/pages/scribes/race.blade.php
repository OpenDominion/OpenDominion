@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $race->name }}</h3>
        </div>


        <div class="box-body table-responsive no-padding">

            <div class="col-md-12 col-md-9">
                <div class="row">
                    <div class="col-md-12 col-md-12">
                        <div class="box-header with-border">
                            <h4 class="box-title">Description</h4>
                        </div>
                        {!! $raceHelper->getRaceDescriptionHtml($race) !!}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-md-12">
                        <div class="box-header with-border">
                            <h4 class="box-title">Racial spell</h4>
                        </div>
                        <table class="table table-striped">
                            @php
                                $racialSpell = $spellHelper->getRacialSelfSpell($race);
                            @endphp
                            <tbody>
                                <tr>
                                    <td>
                                        {{ $racialSpell['name'] }}
                                    </td>
                                    <td>
                                        {{ $racialSpell['description'] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-md-3">
                <table class="table table-striped">
                    <colgroup>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Special Ability</th>
                            <th>Cost</th>
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
                            <td>
                                {!! $perkDescription['value']  !!}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-body table-responsive no-padding">
            <div class="col-md-12 col-md-12">
                <div class="box-header with-border">
                    <h4 class="box-title">Units</h4>
                </div>
                <table class="table table-striped">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>OP</th>
                            <th>DP</th>
                            <th>Special Ability</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($race->units as $unit)
                            <tr>
                                <td>
                                    {{ $unit->name }}
                                </td>
                                <td>
                                    {{ $unit->power_offense }}
                                </td>
                                <td>
                                    {{ $unit->power_defense }}
                                </td>
                                <td>
                                    {!! $unitHelper->getUnitHelpString("unit{$unit->slot}", $race) !!}
                                </td>
                                <td>
                                    {{ $unit->cost_platinum }}p, {{ $unit->cost_ore }}r
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
