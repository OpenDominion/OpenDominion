@extends('layouts.master')

@section('page-header', 'Military')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Military</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-6">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2" class="text-center">Percentages</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center">Population</th>
                                        <th class="text-center">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-center">Peasants:</td>
                                        <td class="text-center">
                                            {{ number_format($selectedDominion->peasants) }} ({{ number_format($populationCalculator->getPopulationPeasantPercentage(), 2) }}%)
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-center">Military:</td>
                                        <td class="text-center">
                                            {{ number_format($populationCalculator->getPopulationMilitary()) }} ({{ number_format($populationCalculator->getPopulationMilitaryPercentage(), 2) }}%)
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-6">
                            <form action="{{ route('dominion.military.change-draft-rate') }}" method="post">
                                {!! csrf_field() !!}

                                <table class="table">
                                    <colgroup>
                                        <col width="50%">
                                        <col width="50%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th colspan="2" class="text-center">Draft Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center">Draft Rate:</td>
                                            <td class="text-center">
                                                <input type="number" name="draft_rate" class="text-center" placeholder="0" min="0" max="100" value="{{ $selectedDominion->draft_rate }}"> %
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td class="text-center">
                                                <button type="submit" class="btn btn-primary">Change</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">

        </div>

    </div>
@endsection
