@extends('layouts.master')

@section('page-header', "{$selectedDominion->name} Status")

@section('content')
    {{ $selectedDominion }}

    {{--<div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="ra ra-capitol ra-fw"></i> {{ $dominion->name }} (#{{ $dominion->round->number }} {{ $dominion->round->name }})
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th colspan="2">Overview</th>
                                    <th colspan="2">Resources</th>
                                    <th colspan="2">Military</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Ruler:</td>
                                    <td>{{ Auth::user()->display_name }}</td>
                                    <td>Platinum:</td>
                                    <td>{{ number_format($dominion->resource_platinum) }}</td>
                                    <td>Morale:</td>
                                    <td>{{ $dominion->morale }}%</td>
                                </tr>
                                <tr>
                                    <td>Race:</td>
                                    <td>{{ $dominion->race->name }}</td>
                                    <td>Food:</td>
                                    <td>{{ number_format($dominion->resource_food) }}</td>
                                    <td>Draftees:</td>
                                    <td>{{ number_format($dominion->military_draftees) }}</td>
                                </tr>
                                <tr>
                                    <td>Land:</td>
                                    <td>NYI</td>
                                    <td>Lumber:</td>
                                    <td>{{ number_format($dominion->resource_lumber) }}</td>
                                    <td>Unit 1:</td>
                                    <td>NYI</td>
                                </tr>
                                <tr>
                                    <td>Peasants:</td>
                                    <td>{{ number_format($dominion->peasants) }}</td>
                                    <td>Mana:</td>
                                    <td>NYI</td>
                                    <td>Unit 2:</td>
                                    <td>NYI</td>
                                </tr>
                                <tr>
                                    <td>Employment:</td>
                                    <td>NYI</td>
                                    <td>Ore:</td>
                                    <td>NYI</td>
                                    <td>Unit 3:</td>
                                    <td>NYI</td>
                                </tr>
                                <tr>
                                    <td>Networth:</td>
                                    <td>{{ number_format($dominion->networth) }}</td>
                                    <td>Gems:</td>
                                    <td>NYI</td>
                                    <td>Unit 4:</td>
                                    <td>NYI</td>
                                </tr>
                                <tr>
                                    <td>Prestige:</td>
                                    <td>{{ number_format($dominion->prestige) }}</td>
                                    <td>Research Points:</td>
                                    <td>NYI</td>
                                    <td>Spies:</td>
                                    <td>NYI</td>
                                </tr>
                                <tr>
                                    <td colspan="2"></td>
                                    <td>Boats:</td>
                                    <td>NYI</td>
                                    <td>Wizards:</td>
                                    <td>NYI</td>
                                </tr>
                                <tr>
                                    <td colspan="4"></td>
                                    <td>ArchMages:</td>
                                    <td>NYI</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>--}}
@endsection
