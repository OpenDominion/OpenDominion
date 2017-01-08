@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-capitol ra-fw"></i> The Dominion of {{ $selectedDominion->name }}</h3>
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
                                <td>{{ $selectedDominion->race->name }}</td>
                            </tr>
                            <tr>
                                <td>Land:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Peasants:</td>
                                <td>{{ number_format($selectedDominion->peasants) }}</td>
                            </tr>
                            <tr>
                                <td>Employment:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Networth:</td>
                                <td>{{ number_format($selectedDominion->networth) }}</td>
                            </tr>
                            <tr>
                                <td>Prestige:</td>
                                <td>{{ number_format($selectedDominion->prestige) }}</td>
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
                                <td>{{ number_format($selectedDominion->resource_platinum) }}</td>
                            </tr>
                            <tr>
                                <td>Food:</td>
                                <td>{{ number_format($selectedDominion->resource_food) }}</td>
                            </tr>
                            <tr>
                                <td>Lumber:</td>
                                <td>{{ number_format($selectedDominion->resource_lumber) }}</td>
                            </tr>
                            <tr>
                                <td>Mana:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Ore:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Gems:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Research Points:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Boats:</td>
                                <td>NYI</td>
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
                                <td>{{ $selectedDominion->morale }}%</td>
                            </tr>
                            <tr>
                                <td>Draftees:</td>
                                <td>{{ number_format($selectedDominion->military_draftees) }}</td>
                            </tr>
                            <tr>
                                <td>Unit 1:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Unit 2:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Unit 3:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Unit 4:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Spies:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Wizards:</td>
                                <td>NYI</td>
                            </tr>
                            <tr>
                                <td>Archmages:</td>
                                <td>NYI</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
