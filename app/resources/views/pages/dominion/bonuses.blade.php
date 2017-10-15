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
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="form-group col-sm-3">
                                    <form action="{{ route('dominion.bonuses.platinum') }}" method="post" role="form">
                                        {!! csrf_field() !!}
                                        <button type="submit" name="platinum" class="btn btn-primary" {{ $selectedDominion->isLocked() || $selectedDominion->daily_platinum ? 'disabled' : null }}>Platinum bonus</button>
                                    </form>
                                </div>
                                <div class="form-group col-sm-3">
                                    <form action="{{ route('dominion.bonuses.land') }}" method="post" role="form">
                                        {!! csrf_field() !!}
                                        <button type="submit" name="land" class="btn btn-primary" {{ $selectedDominion->isLocked() || $selectedDominion->daily_land ? 'disabled' : null }}>Land bonus</button>
                                    </form>
                                </div>
                            </div>
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
                    <p>The platinum bonus <b>instantly</b> gives you platinum equal to 4 times your peasants.</p>
                    <p>The land bonus <b>instantly</b> gives you 20 acres of your home landtype (for example: plains for humans).</p>
                    <p>These rewards can be claimed at any time of a day, but only once that day.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
