@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-axe"></i> Battle Report</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @foreach ($view->sides() as $side)
                                <h5 class="mb-2">{{ $side['label'] }}</h5>
                                <div class="row">
                                    @foreach ($side['combatants'] as $combatant)
                                        @include('partials.dominion.hero-combatant', ['view' => $view, 'combatant' => $combatant])
                                    @endforeach
                                </div>
                            @endforeach
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="text-center">
                                        @if ($battle->finished)
                                            @if ($battle->isDraw())
                                                <h4>Draw!</h4>
                                            @else
                                                <h4>{{ $battle->winnerLabel() }} {{ $battle->winningCombatants()->whereNotNull('hero_id')->count() > 1 ? 'win' : 'wins' }}!</h4>
                                            @endif
                                        @else
                                            <h4>Combat in progress...</h4>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @include('partials.dominion.hero-combat-log', ['view' => $view, 'battle' => $battle])
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    @include('partials.dominion.hero-combat')
                </div>
            </div>
        </div>

    </div>
@endsection
