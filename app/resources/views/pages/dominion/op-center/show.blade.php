@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="row">

                <div class="col-sm-12 col-md-8">
                    @if (!$infoOpService->hasInfoOp($selectedDominion->realm, $dominion, 'clear_sight'))
                        no clear sight :(
                    @else
                        @php
                            $infoOp = $infoOpService->getInfoOp($selectedDominion->realm, $dominion, 'clear_sight');
                        @endphp
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">
                                    <i class="fa fa-bar-chart"></i> The Dominion of {{ $dominion->name }}
                                    @if ($infoOp->isStale())
                                        <span class="label label-warning">Stale</span>
                                    @endif
                                </h3>
                                <div class="pull-right">
                                    <button class="btn btn-success"><i class="fa fa-refresh"></i> Refresh (2,000 mana)</button>
                                </div>
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
                                                    <td>NYI</td>
                                                </tr>
                                                <tr>
                                                    <td>Race:</td>
                                                    <td>{{ $infoOp->data['race'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Land:</td>
                                                    <td>{{ number_format($infoOp->data['land']) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    status
                </div>
                <div class="col-sm-12 col-md-4">
                    <div class="row">
                        <div class="col-sm-12">
                            active spells
                        </div>
                        <div class="col-sm-12">
                            imps
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">

                <div class="col-sm-12 col-md-6">
                    buildings
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="row">
                        <div class="col-sm-12">
                            units
                        </div>
                        <div class="col-sm-12">
                            land
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This is the Op Center result for <b>{{ $dominion->name }}</b> from realm {{ $dominion->realm->name }} (#{{ $dominion->realm->number }}).</p>

                    {{-- stale warning --}}

                    {{-- op --}}
                    {{-- dp --}}
                    {{-- land --}}
                    {{-- networth --}}
                    {{-- invade button --}}
                </div>
            </div>
        </div>

    </div>
@endsection