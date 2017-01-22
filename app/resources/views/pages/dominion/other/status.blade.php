@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-capitol"></i> The Dominion of {{ $dominion->name }}</h3>
        </div>
        <div class="box-body no-padding">
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
                                <td>{{ Auth::user()->display_name }}</td>
                            </tr>
                            <tr>
                                <td>Race:</td>
                                <td>{{ $dominion->race->name }}</td>
                            </tr>
                            <tr>
                                <td>Land:</td>
                                <td>{{ number_format($landCalculator->getTotalLand()) }}</td>
                            </tr>
                            <tr>
                                <td>Peasants:</td>
                                <td>{{ number_format($dominion->peasants) }}</td>
                            </tr>
                            <tr>
                                <td>Employment:</td>
                                <td>{{ number_format($populationCalculator->getEmploymentPercentage()) }}%</td>
                            </tr>
                            <tr>
                                <td>Networth:</td>
                                <td>{{ number_format($dominion->networth) }}</td>
                            </tr>
                            <tr>
                                <td>Prestige:</td>
                                <td>{{ number_format($dominion->prestige) }}</td>
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
                                <td>Platinum:</td>
                                <td>{{ number_format($dominion->resource_platinum) }}</td>
                            </tr>
                            <tr>
                                <td>Food:</td>
                                <td>{{ number_format($dominion->resource_food) }}</td>
                            </tr>
                            <tr>
                                <td>Lumber:</td>
                                <td>{{ number_format($dominion->resource_lumber) }}</td>
                            </tr>
                            <tr>
                                <td>Mana:</td>
                                <td>{{ number_format($dominion->resource_mana) }}</td>
                            </tr>
                            <tr>
                                <td>Ore:</td>
                                <td>{{ number_format($dominion->resource_ore) }}</td>
                            </tr>
                            <tr>
                                <td>Gems:</td>
                                <td>{{ number_format($dominion->resource_gems) }}</td>
                            </tr>
                            <tr>
                                <td>Research Points:</td>
                                <td>{{ number_format($dominion->resource_tech) }}</td>
                            </tr>
                            <tr>
                                <td>Boats:</td>
                                <td>{{ number_format($dominion->resource_boats) }}</td>
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
                                <td>Morale:</td>
                                <td>{{ number_format($dominion->morale) }}%</td>
                            </tr>
                            <tr>
                                <td>Draftees:</td>
                                <td>{{ number_format($dominion->military_draftees) }}</td>
                            </tr>
                            <tr>
                                <td>{{ $dominion->race->units->get(0)->name }}:</td>
                                <td>{{ number_format($dominion->military_unit1) }}</td>
                            </tr>
                            <tr>
                                <td>{{ $dominion->race->units->get(1)->name }}:</td>
                                <td>{{ number_format($dominion->military_unit2) }}</td>
                            </tr>
                            <tr>
                                <td>{{ $dominion->race->units->get(2)->name }}:</td>
                                <td>{{ number_format($dominion->military_unit3) }}</td>
                            </tr>
                            <tr>
                                <td>{{ $dominion->race->units->get(3)->name }}:</td>
                                <td>{{ number_format($dominion->military_unit4) }}</td>
                            </tr>
                            <tr>
                                <td>Spies:</td>
                                <td>{{ number_format($dominion->military_spies) }}</td>
                            </tr>
                            <tr>
                                <td>Wizards:</td>
                                <td>{{ number_format($dominion->military_wizards) }}</td>
                            </tr>
                            <tr>
                                <td>Archmages:</td>
                                <td>{{ number_format($dominion->military_archmages) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
