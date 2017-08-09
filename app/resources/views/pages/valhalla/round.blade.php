@extends('layouts.topnav')

@section('content')
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-angel-wings"></i> Valhalla for round {{ $round->number }}: {{ $round->name }}</h3>
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
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-realms']) }}">The Strongest Realms</a>
                    {{-- strongest good dominions --}}
                    {{-- strongest evil dominions --}}
                    {{-- strongest monarchs --}}
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-dominions']) }}">The Largest Dominions</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-realms']) }}">The Largest Realms</a>
                    {{-- largest good dominions --}}
                    {{-- largest evil dominions --}}
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
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-humans']) }}">The Strongest Humans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'strongest-nomads']) }}">The Strongest Nomads</a>
                </div>
                <div class="col-sm-6 text-center">
                    <b>Largest</b><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-humans']) }}">The Largest Humans</a><br>
                    <a href="{{ route('valhalla.round.type', [$round, 'largest-nomads']) }}">The Largest Nomads</a>
                </div>
            </div>

        </div>
    </div>
@endsection
