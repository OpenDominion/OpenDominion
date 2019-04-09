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
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-dwarves']) }}">The Strongest Dwarves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-firewalkers']) }}">The Strongest Firewalkers</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-goblins']) }}">The Strongest Goblins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-humans']) }}">The Strongest Humans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-lizardfolk']) }}">The Strongest Lizardfolk</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-nomads']) }}">The Strongest Nomads</a>
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dwarves']) }}">The Largest Dwarves</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-firewalkers']) }}">The Largest Firewalkers</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-goblins']) }}">The Largest Goblins</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-humans']) }}">The Largest Humans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-lizardfolk']) }}">The Largest Lizardfolk</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-nomads']) }}">The Largest Nomads</a>
                </div>
            </div>

        </div>
    </div>
@endsection
