@extends('layouts.master')

@php
    $target = $selectedDominion;
    $pageHeader = 'Tech Advisor';
    if($targetDominion != null) {
        $target = $targetDominion;
        $pageHeader .= ' for '.$target->name;
    }
    $improvementsData = $infoMapper->mapImprovements($target);
    $techsData = $infoMapper->mapTechs($target);
@endphp

@section('page-header', $pageHeader)

@section('content')
    @include('partials.dominion.advisor-selector')
    <div class="row">

        <div class="col-md-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-flask"></i> Technological Advances</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.techs-table', ['data' => $techsData])
                </div>
            </div>
        </div>

        <div class="col-md-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-fizzing-flask"></i> Tech Bonuses</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.techs-combined', ['data' => $techsData])
                </div>
            </div>
        </div>

    </div>

@endsection
