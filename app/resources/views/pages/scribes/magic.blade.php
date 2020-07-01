@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Magic</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Magic will let your wizards cast a variety of spells, giving temporary bonuses, information or damaging your enemies.</p>
                    <p>All spells cost mana, which is produced by towers. The cost of each spell is based on a multiplier of your land size.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Magic">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Spells</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Self</h4>
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
                                <th></th>
                                <th></th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getSelfSpells(null)->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>&nbsp;</td>
                                    <td>{{ $operation['mana_cost'] }}x</td>
                                    <td>{{ isset($operation['duration']) ? $operation['duration'] : '--' }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>&nbsp;</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Information gathering</h4>
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
                                <th></th>
                                <th></th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getInfoOpSpells(null)->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td></td>
                                    <td>{{ $operation['mana_cost'] }}x</td>
                                    <td>{{ isset($operation['duration']) ? $operation['duration'] : '--' }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>&nbsp;</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Black Ops</h4>
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
                                <th></th>
                                <th></th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getHostileSpells(null)->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td></td>
                                    <td>{{ $operation['mana_cost'] }}x</td>
                                    <td>{{ isset($operation['duration']) ? $operation['duration'] : '--' }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>&nbsp;</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Racial</h4>
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
                                <th></th>
                                <th>Race(s)</th>
                                <th>Cost multiplier</th>
                                <th>Duration (hours)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($spellHelper->getRacialSelfSpells(null)->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{$operation['races']->implode(', ')}}</td>
                                    <td>{{ $operation['mana_cost'] }}x</td>
                                    <td>{{ isset($operation['duration']) ? $operation['duration'] : '--' }}</td>
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
