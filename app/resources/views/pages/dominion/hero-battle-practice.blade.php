@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <form class="form-horizontal" action="{{ route('dominion.heroes.battles.practice') }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-axe"></i> Practice Battles</h3>
                    </div>
                    <div class="box-body table-responsive">
                        <table class="table">
                            <colgroup>
                                <col width="5%">
                                <col width="35%">
                                <col width="60%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Encounter</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="radio" id="encounter-default" name="encounter" value="default" checked /></td>
                                    <td><label for="encounter-default" style="font-weight: normal;">Evil Twin</label></td>
                                    <td>Clone with your current stats</td>
                                </tr>
                                @foreach ($heroEncounterHelper->getEncounters() as $key => $practiceBattle)
                                    <tr>
                                        <td><input type="radio" id="encounter-{{ $key }}" name="encounter" value="{{ $key }}" /></td>
                                        <td><label for="encounter-{{ $key }}" style="font-weight: normal;">{{ $practiceBattle['name'] }}</label></td>
                                        <td>{{ $practiceBattle['source'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" type="submit">Start Practice Battle</button>
                    </div>
                </div>
            </form>

        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    @include('partials.dominion.hero-combat')
                </div>
            </div>
        </div>

    </div>
@endsection
