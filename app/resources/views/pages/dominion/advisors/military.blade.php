@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Military Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }

    $militaryData = $infoMapper->mapMilitary($target, false);
    $resourceData = $infoMapper->mapResources($target);
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
                    @include('partials.dominion.info.military-training-table', ['data' => $militaryData, 'isOp' => false, 'race' => $target->race ])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-clock-o"></i> Units returning from battle</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            @include('partials.dominion.info.military-returning-table', ['data' => $militaryData, 'isOp' => false, 'race' => $target->race ])
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-clock-o"></i> Resources returning from battle</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            @include('partials.dominion.info.resources-incoming-table', ['data' => $resourceData])
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
