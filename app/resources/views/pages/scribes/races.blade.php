@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Races</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Players must choose a race for their dominion. Each race has unique bonuses, military units, and spells.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Races">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="float-start">
                <a href="{{ route('scribes.all-races') }}">View All Playable Races</a>
            </div>
            <div class="float-end">
                @if ($legacy)
                    <a href="{{ route('scribes.races') }}">View Playable Races</a>
                @else
                    <a href="{{ route('scribes.legacy-races') }}">View Legacy Races</a>
                @endif
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body no-padding">
            <div class="row">
                <div class="col-md-12 col-md-6">
                    <div class="card-header">
                        <h4 class="card-title">Good Alignment</h4>
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <tbody>
                            @foreach ($goodRaces as $race)
                                <tr>
                                    <td>
                                        <a href="{{ route('scribes.race', $race['key']) }}">{{ $race['name'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col-md-12 col-md-6">
                    <div class="card-header">
                        <h4 class="card-title">Evil Alignment</h4>
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <tbody>
                            @foreach ($evilRaces as $race)
                                <tr>
                                    <td>
                                        <a href="{{ route('scribes.race', $race['key']) }}">{{ $race['name'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
