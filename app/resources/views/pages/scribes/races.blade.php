@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Races</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Players must choose a race for their dominion. Each race has unique bonuses, military units, and spells.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Races">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <div class="pull-left">
                <a href="{{ route('scribes.all-races') }}">View All Playable Races</a>
            </div>
            <div class="pull-right">
                @if ($legacy)
                    <a href="{{ route('scribes.races') }}">View Playable Races</a>
                @else
                    <a href="{{ route('scribes.legacy-races') }}">View Legacy Races</a>
                @endif
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-body no-padding">
            <div class="row">
                <div class="col-md-12 col-md-6">
                    <div class="box-header with-border">
                        <h4 class="box-title">Good Alignment</h4>
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
                    <div class="box-header with-border">
                        <h4 class="box-title">Evil Alignment</h4>
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
