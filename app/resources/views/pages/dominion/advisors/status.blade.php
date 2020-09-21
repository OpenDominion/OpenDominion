@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Status Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-sm-12 col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> The Dominion of {{ $selectedDominion->name }}</h3>
                </div>
                <div class="box-body no-padding">
                    @include('partials.dominion.info.status', ['data' => $infoMapper->mapStatus($selectedDominion, false), 'race' => $selectedDominion->race, ])
                </div>
            </div>
        </div>
    </div>

@endsection