@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Lifetime Ranking for player: {{ $player->display_name }}</h3>
        </div>

        @if (!$dominions->isEmpty())
            <div class="box-body table-responsive">
                <div class="row">
                    @foreach ($leagues as $league)
                        <div class="col-md-12">
                            <h4>{{ $league->description }}</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Round</th>
                                        <th>Dominion</th>
                                        <th class="text-center">Race</th>
                                        <th class="text-center">Realm</th>
                                        <th class="text-center">Land</th>
                                        <th class="text-center">Networth</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dominions->where('round.round_league_id', $league->id) as $dominion)
                                        <tr>
                                            <td>
                                                <a href="{{ route('valhalla.round', $dominion->round_id) }}">
                                                    {{ $dominion->round->name }}
                                                </a>
                                            </td>
                                            <td>
                                                {{ $dominion->name }}
                                            </td>
                                            <td class="text-center">
                                                {{ $dominion->race->name }}
                                            </td>
                                            <td class="text-center">
                                                {{ $dominion->realm->number }}
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($landCalculator->getTotalLand($dominion)) }}
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($networthCalculator->getDominionNetworth($dominion)) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if (isset($dailyRankings[$league->id]))
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Aggregated Rankings</th>
                                            <th>Average</th>
                                            <th>Total</th>
                                            <th>Best</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Most Decorated (Titles)</td>
                                            <td>{{ number_format($dailyRankings[$league->id]->where('rank', 1)->count() / $dominions->where('round.round_league_id', $league->id)->count(), 2) }}</td>
                                            <td>{{ number_format($dailyRankings[$league->id]->where('rank', 1)->count()) }}</td>
                                            <td>--</td>
                                        </tr>
                                        @foreach ($rankingsHelper->getRankings() as $ranking)
                                            @php $bestRank = $dailyRankings[$league->id]->where('key', $ranking['key'])->min('rank'); @endphp
                                            <tr>
                                                <td>{{ $ranking['name'] }} ({{ $ranking['stat_label'] }})</td>
                                                <td>{{ number_format($dailyRankings[$league->id]->where('key', $ranking['key'])->avg('value'), 2) }}</td>
                                                <td>{{ number_format($dailyRankings[$league->id]->where('key', $ranking['key'])->sum('value')) }}</td>
                                                <td>{{ $bestRank ? '#'.$bestRank : '--' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="box-body">
                <p>No records found.</p>
            </div>
        @endif
    </div>
@endsection
