@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Achievements</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom: 10px;">
                    <i class="ra {{ isset($user->settings['boardavatar']) ? $user->settings['boardavatar'] : 'ra-player' }} text-muted pull-left" style="font-size: 64px;"></i>
                    <h4 style="margin-bottom: 5px;">{{ $user->display_name }}</h4>
                    <div class="text-muted">registered {{ $user->created_at->format('Y-m-d') }}</div>
                </div>
                @foreach ($user->achievements()->ordered()->get() as $achievement)
                    <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom: 10px;">
                        <div class="btn-block" style="border: 1px solid #777; border-radius: 10px; padding: 10px; min-height: 82px;">
                            <i class="ra {{ $achievement['icon'] }} pull-left text-muted" style="font-size: 48px;"></i>
                            <div class="text-bold">{{ $achievement['name'] }}</div>
                            <div class="text-muted">{{ $achievement['description'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Lifetime Ranking</h3>
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
