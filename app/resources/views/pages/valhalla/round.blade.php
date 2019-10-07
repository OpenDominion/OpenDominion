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
