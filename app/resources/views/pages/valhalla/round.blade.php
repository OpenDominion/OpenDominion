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
            <div class="row">
                <div class="col-sm-6 text-center">
                    <b>Strongest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-dominions']) }}">The Strongest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-good-dominions']) }}">The Strongest Dominions (Good)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-evil-dominions']) }}">The Strongest Dominions (Evil)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-realms']) }}">The Strongest Realms</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-good-realms']) }}">The Strongest Realms (Good)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-evil-realms']) }}">The Strongest Realms (Evil)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-packs']) }}">The Strongest Packs</a>
                    {{-- strongest monarchs --}}
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dominions']) }}">The Largest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-good-dominions']) }}">The Largest Dominions (Good)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-evil-dominions']) }}">The Largest Dominions (Evil)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-realms']) }}">The Largest Realms</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-good-realms']) }}">The Largest Realms (Good)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-evil-realms']) }}">The Largest Realms (Evil)</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-packs']) }}">The Largest Packs</a>
                    {{-- largest monarchs --}}
                </div>
            </div>

            {{-- extended rankings --}}

            {{--<div class="row">
                <div class="col-md-12 text-center">
                    <a href="#">Wonders of the World</a>
                </div>
            </div>--}}

            {{-- magic and spy rankings --}}

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Rankings by Race</b>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 text-center">
                    <b>Strongest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-dark-elf']) }}">The Strongest Dark Elves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-dwarf']) }}">The Strongest Dwarves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-firewalker']) }}">The Strongest Firewalkers</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-gnome']) }}">The Strongest Gnomes</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-goblin']) }}">The Strongest Goblins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-halfling']) }}">The Strongest Halflings</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-human']) }}">The Strongest Humans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-icekin']) }}">The Strongest Icekin</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-lizardfolk']) }}">The Strongest Lizardfolk</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-lycanthrope']) }}">The Strongest Lycanthropes</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-merfolk']) }}">The Strongest Merfolk</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-nomad']) }}">The Strongest Nomads</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-nox']) }}">The Strongest Nox</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-spirit']) }}">The Strongest Spirits</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-sylvan']) }}">The Strongest Sylvans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-troll']) }}">The Strongest Trolls</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-undead']) }}">The Strongest Undead</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-wood-elf']) }}">The Strongest Wood Elves</a>
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dark-elf']) }}">The Largest Dark Elves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dwarf']) }}">The Largest Dwarves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-firewalker']) }}">The Largest Firewalkers</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-gnome']) }}">The Largest Gnomes</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-goblin']) }}">The Largest Goblins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-halfling']) }}">The Largest Halflings</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-human']) }}">The Largest Humans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-icekin']) }}">The Largest Icekin</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-lizardfolk']) }}">The Largest Lizardfolk</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-lycanthrope']) }}">The Largest Lycanthropes</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-merfolk']) }}">The Largest Merfolk</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-nomad']) }}">The Largest Nomads</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-nox']) }}">The Largest Nox</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-spirit']) }}">The Largest Spirits</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-sylvan']) }}">The Largest Sylvans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-troll']) }}">The Largest Trolls</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-undead']) }}">The Largest Undead</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-wood-elf']) }}">The Largest Wood Elves</a>
                </div>
            </div>

        </div>
    </div>
@endsection
