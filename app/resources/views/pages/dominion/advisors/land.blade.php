@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Land Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }

    $data = $infoMapper->mapLand($target);
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-honeycomb"></i> {{ $pageHeader }}</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.land-table', ['data' => $data, 'race' => $target->race])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming land breakdown</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.land-incoming-table', ['data' => $data, 'race' => $target->race])

                </div>
            </div>
        </div>

    </div>
@endsection
