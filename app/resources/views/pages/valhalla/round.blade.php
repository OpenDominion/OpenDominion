@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-angel-wings"></i> Valhalla for round {{ number_format($round->number) }}: {{ $round->name }}</h3>
        </div>
        <div class="box-body">

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Overall Rankings</b>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <b>Strongest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-dominions']) }}">The Strongest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-realms']) }}">The Strongest Realms</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-packs']) }}">The Strongest Packs</a>
                    {{-- strongest monarchs --}}
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dominions']) }}">The Largest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-realms']) }}">The Largest Realms</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-packs']) }}">The Largest Packs</a>
                    {{-- largest monarchs --}}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Extended Rankings</b>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-conquered']) }}">Largest Attacking Dominions<br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-total-land-conquered']) }}">Largest Attacking Realms<br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-attacking-success']) }}">Most Victorious Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-attacking-success']) }}">Most Victorious Realms</a><br>
                </div>
                <div class="col-sm-6 text-center">
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-explored']) }}">Largest Exploring Dominions<br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-total-land-explored']) }}">Largest Exploring Realms<br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-prestige']) }}">Most Prestigious Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-prestige']) }}">Most Prestigious Realms</a><br>
                    <!--
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-defending-success']) }}">Most Fortified Dominions</a><br>
                    -->
                </div>
            </div>

            {{--<div class="row">
                <div class="col-md-12 text-center">
                    <a href="#">Wonders of the World</a>
                </div>
            </div>--}}

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Magic and Spy Rankings</b>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <b>Spies</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-espionage-success']) }}">Most Successful Spies</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-spy-prestige']) }}">Most Prestigious Spies</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-platinum-stolen']) }}">Top Platinum Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-food-stolen']) }}">Top Food Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-lumber-stolen']) }}">Top Lumber Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-mana-stolen']) }}">Top Mana Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-ore-stolen']) }}">Top Ore Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-gems-stolen']) }}">Top Gem Thieves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-saboteurs']) }}">Top Saboteurs</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-magical-assassins']) }}">Top Magical Assassins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-military-assassins']) }}">Top Military Assassins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-snare-setters']) }}">Top Snare Setters</a><br>
                    <!-- Top Demoralizers -->
                </div>
                <div class="col-sm-6 text-center">
                    <b>Wizards</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-spell-success']) }}">Most Successful Wizards</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-wizard-prestige']) }}">Most Prestigious Wizards</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-fire']) }}">Masters of Fire</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-plague']) }}">Masters of Plague</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-swarm']) }}">Masters of Swarm</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-lightning']) }}">Masters of Lightning</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-water']) }}">Masters of Water</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-earth']) }}">Masters of Earth</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-top-spy-disbanders']) }}">Top Spy Disbanders</a><br>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Rankings by Race</b>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <b>Strongest</b><br>
                    @foreach ($races as $race)
                        @php $raceSlug = 'strongest-' . str_slug($race); @endphp
                        <a href="{{ route('valhalla.round.type', [$round, $raceSlug]) }}">The Strongest {{ str_plural($race) }}</a><br>
                    @endforeach
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    @foreach ($races as $race)
                        @php $raceSlug = 'largest-' . str_slug($race); @endphp
                        <a href="{{ route('valhalla.round.type', [$round, $raceSlug]) }}">The Largest {{ str_plural($race) }}</a><br>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
@endsection
