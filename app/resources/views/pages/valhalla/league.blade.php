@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-angel-wings"></i> Valhalla for {{ $league->description }}</h3>
        </div>
        <div class="box-body">

            <div class="row">
                <div class="col-md-12 text-center">
                    <b>Lifetime Standings</b>
                </div>
            </div>
            <div class="row">
                @foreach ($rankingsHelper->getRankings() as $ranking)
                    <div class="col-sm-6 text-center">
                        <a href="{{ route('valhalla.league.type', [$league, $ranking['key']]) }}">{{ $ranking['name'] }}</a><br>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endsection
