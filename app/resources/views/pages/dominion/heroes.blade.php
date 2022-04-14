@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Heroes</h3>
                </div>
                @if ($heroes->isEmpty())
                    <form class="form-horizontal" action="{{ route('dominion.heroes.create') }}" method="post" role="form">
                        @csrf
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Name</label>
                                        <div class="col-sm-9">
                                            <input name="name" id="name" class="form-control" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Class</label>
                                        <div class="col-sm-9">
                                            <select name="class" class="form-control">
                                                @foreach ($heroHelper->getClasses() as $class)
                                                    <option value="{{ $class['key'] }}">
                                                        {{ $class['name'] }} - Gains double XP from {{ $class['xp_bonus_type'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Trade</label>
                                        <div class="col-sm-9">
                                            <select name="trade" class="form-control">
                                                @foreach ($heroHelper->getTrades() as $trade)
                                                    <option value="{{ $trade['key'] }}">
                                                        {{ $trade['name'] }} - {{ $trade['perk_type'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    Placeholder image
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Create Hero</button>
                        </div>
                    </form>
                @else
                    @foreach ($heroes as $hero)
                        <form action="{{ route('dominion.heroes') }}" method="post" role="form">
                            @csrf
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row" style="font-size: 36px;">
                                            <div class="col-xs-3">
                                                <i class="ra ra-knight-helmet" title="Helmet" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-sword" title="Sword" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-shield" title="Shield" data-toggle="tooltip"></i>
                                            </div>
                                            <div class="col-xs-6">
                                                Placeholder image
                                            </div>
                                            <div class="col-xs-3">
                                                <i class="ra ra-gold-bar" title="Alchemist" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-falling" title="Ooopsie" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-roast-chicken" title="Hangry" data-toggle="tooltip"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center">
                                            {{ $hero->name }}
                                        </div>
                                        <div class="text-center">
                                            Level 1 {{ $heroHelper->getClassDisplayName($hero->class) }}
                                        </div>
                                        <div class="text-center">
                                            {{ $hero->experience }} / 12,000 XP
                                        </div>
                                        <div>
                                            Trade:<br/>
                                            {{ $heroHelper->getTradeDisplayName($hero->trade) }} - +9.34% platinum production
                                        </div>
                                        <div>
                                            Gear:<br/>
                                            Cool Dagger - +5% spy strength
                                        </div>
                                        <div>
                                            Abilities:<br/>
                                            Cool Ability - +1% offense when you have less than 10,000 food
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>You can only have one hero at a time.</p>
                    <p>Your hero gains experience and levels up, increasing it's trade bonus and unlocking new upgrades.</p>
                    <p>Your hero gains 175xp per invasion, 2xp per acre explored, 2xp per info op, 8xp per black op.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
