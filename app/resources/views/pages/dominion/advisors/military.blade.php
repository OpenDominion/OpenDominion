@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Military Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }

    $data = $infoMapper->mapMilitary($target, false);
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Units in training and home</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.military-training-table', ['data' => $data, 'isOp' => false, 'race' => $selectedDominion->race ])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Units returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.military-returning-table', ['data' => $data, 'isOp' => false, 'race' => $selectedDominion->race ])
                </div>
            </div>
        </div>

    </div>
@endsection
