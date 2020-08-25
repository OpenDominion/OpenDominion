@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Construction Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }

    $data = $infoMapper->mapBuildings($target);
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> {{ $pageHeader }}</h3>
                    <span class="pull-right">Barren Land: <strong>{{ number_format($landCalculator->getTotalBarrenLand($target)) }}</strong></span>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.construction-constructed-table', ['data' => $data])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming building breakdown</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.construction-constructing-table', ['data' => $data])
                </div>
            </div>
        </div>

    </div>
@endsection
