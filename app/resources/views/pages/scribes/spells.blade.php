@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Spells</h3>
        </div>
        <div class="box-body no-padding">
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="125px">
                            <col width="125px">
                            <col width="125px">
                            <col width="125px">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Self spell</th>
                                <th></th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getSelfSpells(null) as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>&nbsp;</td>
                                    <td>{{ $operation['mana_cost'] }}</td>
                                    <td>{{ $operation['duration'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="125px">
                            <col width="125px">
                            <col width="125px">
                            <col width="125px">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Racial spell</th>
                                <th>Race(s)</th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getRacialSelfSpells(null) as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{$operation['races']->implode(', ')}}</td>
                                    <td>{{ $operation['mana_cost'] }}</td>
                                    <td>{{ $operation['duration'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="125px">
                            <col width="125px">
                            <col width="125px">
                            <col width="125px">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Information spell</th>
                                <th></th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getInfoOpSpells(null) as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td></td>
                                    <td>{{ $operation['mana_cost'] }}</td>
                                    <td></td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
