@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-angel-wings"></i> Valhalla for round {{ number_format($round->number) }}: {{ $round->name }}</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Round information</b>
                </div>
            </div>
            <div class="row row">
                <div class="col-md-2"></div>
                <div class="col-md-4 text-center">
                    <table class="table">
                        <thead>
                            <tr>
                                <th colspan="2" class="text-center">Statistics</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Date</td>
                                <td class="text-center">
                                    {{ $round->start_date->toFormattedDateString() }} to {{ $round->end_date->toFormattedDateString() }}
                                </td>
                            </tr>
                            <tr>
                                <td>Days</td>
                                <td class="text-center">
                                    {{ $round->durationInDays() }}
                                </td>
                            </tr>
                            <tr>
                                <td>Players</td>
                                <td class="text-center">
                                    {{ $round->dominions->count() }}
                                </td>
                            </tr>
                            <tr>
                                <td>Realms</td>
                                <td class="text-center">
                                    {{ $round->realms->count() }}
                                </td>
                            </tr>
                            <tr>
                                <td>Packs</td>
                                <td class="text-center">
                                    {{ $round->packs->count() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4 text-center">
                    <table class="table">
                        <thead>
                            <tr>
                                <th colspan="2" class="text-center">Rules</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Realm size</td>
                                <td class="text-center">
                                    {{ $round->realm_size }}
                                </td>
                            </tr>
                            <tr>
                                <td>Pack size</td>
                                <td class="text-center">
                                    {{ $round->pack_size }}
                                </td>
                            </tr>
                            <tr>
                                <td>Players per race</td>
                                <td class="text-center">
                                    {{ $round->players_per_race }}
                                </td>
                            </tr>
                            <tr>
                                <td>Mixed alignment</td>
                                <td class="text-center">
                                    {{ $round->mixed_alignment }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-2"></div>
            </div>

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
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-conquered']) }}">Largest Attacking Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-total-land-conquered']) }}">Largest Attacking Realms</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-attacking-success']) }}">Most Victorious Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-attacking-success']) }}">Most Victorious Realms</a><br>
                </div>
                <div class="col-sm-6 text-center">
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-total-land-explored']) }}">Largest Exploring Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-total-land-explored']) }}">Largest Exploring Realms</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-prestige']) }}">Most Prestigious Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'realm-stat-prestige']) }}">Most Prestigious Realms</a><br>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Wonders of the World</b>
                </div>
            </div>
            <div class="row form-group">
                <div class="col-sm-6 text-center">
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-wonder-damage']) }}">Most Wonder Damage</a><br>
                </div>
                <div class="col-sm-6 text-center">
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-wonders-destroyed']) }}">Most Wonders Destroyed</a><br>
                </div>
            </div>

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
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-spies-executed']) }}">Most Spies Executed</a><br>
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
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-wizards-executed']) }}">Most Wizards Executed</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-fire']) }}">Masters of Fire</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-plague']) }}">Masters of Plague</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-swarm']) }}">Masters of Swarm</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'stat-masters-of-air']) }}">Masters of Air</a><br>
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
