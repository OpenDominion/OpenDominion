@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-plus"></i> Daily bonuses </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6 text-center">
                            <form action="{{ route('dominion.bonuses.platinum') }}" method="post" role="form">
                                {!! csrf_field() !!}
                                <button type="submit" name="platinum" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || $selectedDominion->daily_platinum ? 'disabled' : null }}>Platinum Bonus</button>
                            </form>
                        </div>
                        <div class="col-xs-6 text-center">
                            <form action="{{ route('dominion.bonuses.land') }}" method="post" role="form">
                                {!! csrf_field() !!}
                                <button type="submit" name="land" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || $selectedDominion->daily_land ? 'disabled' : null }}>Land Bonus</button>
                            </form>
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
                    <p>The Platinum Bonus instantly gives you {{ number_format($selectedDominion->peasants * 4) }} platinum.</p>
                    <p>The Land Bonus instantly gives you 20 acres of {{ str_plural($selectedDominion->race->home_land_type) }}.</p>
                    <p>Both bonuses can be claimed once per day.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
