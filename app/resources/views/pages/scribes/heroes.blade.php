@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Heroes</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Each dominion can select one hero that gains experience and levels up, increasing a passive bonus based on its class.</p>
                    <p>A hero gains 1 XP per acre conquered, 1 XP per successful info operation, and 5 XP per successful black/war operation.</p>
                    <p>A hero can be retired and replaced with another. The new hero will start with XP equal to half that of its predecessor.</p>
                </div>
                <div class="col-md-6">
                    <table class="table table-striped">
                        @foreach ($heroHelper->getTrades() as $trade)
                            <tr>
                                <td class="text-right text-bold">{{ $trade['name'] }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $trade['perk_type'])) }}</td>
                            </tr>
                        @endforeach
                    </table>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Heroes">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Hero Bonuses</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Level</th>
                        <th>XP</th>
                        @foreach ($heroHelper->getTrades() as $trade)
                            <th>{{ $trade['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($heroCalculator->getExperienceLevels() as $level)
                        @if ($level['level'] !== 0)
                            <tr>
                                <td>{{ $level['level'] }}</td>
                                <td>{{ $level['xp'] }}</td>
                                @foreach ($heroHelper->getTrades() as $trade)
                                    <th>{{ number_format($heroCalculator->calculateTradeBonus($trade['perk_type'], $level['level']), 2) }}%</th>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
