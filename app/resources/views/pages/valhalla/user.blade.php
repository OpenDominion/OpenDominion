@extends('layouts.topnav')

@section('content')
    {{-- Profile + League Stats grid --}}
    <div class="row gy-3 mb-3">
        {{-- Profile --}}
        <div class="col-md-6 col-lg-4">
            <div class="card card-primary h-100 mb-0">
                <div class="card-header">
                    <span class="card-title">Profile</span>
                    @if (Auth::check() && Auth::id() === $user->id)
                        <div class="float-end">
                            <a href="{{ route('settings') }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-cog"></i> Edit
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-4 text-center">
                            @if (Auth::check() && Auth::id() === $user->id)
                                <a href="{{ route('message-board.avatar') }}" title="Change icon" class="text-muted">
                                    <i class="ra {{ isset($user->settings['boardavatar']) ? $user->settings['boardavatar'] : 'ra-player' }}" style="font-size: 96px; line-height: 1;"></i>
                                </a>
                            @else
                                <i class="ra {{ isset($user->settings['boardavatar']) ? $user->settings['boardavatar'] : 'ra-player' }} text-muted" style="font-size: 96px; line-height: 1;"></i>
                            @endif
                        </div>
                        <div class="col-8">
                            @php $countryCode = $user->getSetting('country'); @endphp
                            @php $countryName = $countryCode ? $countryHelper->getName($countryCode) : null; @endphp
                            <h4 class="mb-1">{{ $user->display_name }}</h4>
                            @if ($roleHtml = $user->displayRoleHtml())
                                <div class="text-muted small mb-1">{!! $roleHtml !!}</div>
                            @endif
                            @if ($countryCode && $countryName)
                                <div class="text-muted small mb-1">
                                    <span class="fi fi-{{ $countryCode }} me-1" style="vertical-align: baseline;"></span>
                                    {{ $countryName }}
                                </div>
                            @endif
                            <div class="text-muted small">
                                Registered {{ $user->created_at->format('Y-m-d') }}
                            </div>
                            @if ($user->last_online)
                                @php
                                    $daysSinceOnline = $user->last_online->diffInDays(now());
                                    if ($daysSinceOnline < 30) {
                                        $lastSeenLabel = 'in the past month';
                                    } elseif ($daysSinceOnline < 365) {
                                        $lastSeenLabel = 'in the past year';
                                    } else {
                                        $years = (int) floor($daysSinceOnline / 365);
                                        $lastSeenLabel = $years . ' year' . ($years === 1 ? '' : 's') . ' ago';
                                    }
                                @endphp
                                <div class="text-muted small">
                                    Last seen {{ $lastSeenLabel }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- One card per league with stats --}}
        @foreach ($leagues as $league)
            @if (!isset($leagueStats[$league->id])) @continue @endif
            @php $stats = $leagueStats[$league->id]; @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card card-primary h-100 mb-0">
                    <div class="card-header">
                        <span class="card-title">{{ $league->description }}</span>
                    </div>
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <tr>
                                <td class="ps-3">Rounds Played</td>
                                <td class="text-end pe-3">{{ number_format($stats['rounds_played']) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Highest Land</td>
                                <td class="text-end pe-3">{{ $stats['best_land'] ? number_format($stats['best_land']) : '--' }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Favorite Race</td>
                                <td class="text-end pe-3">{{ $stats['top_race'] ?: '--' }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Round Wins</td>
                                <td class="text-end pe-3">{{ number_format($stats['round_wins']) }}</td>
                            </tr>
                            <tr>
                                <td class="ps-3">Realm Wins</td>
                                <td class="text-end pe-3">{{ number_format($stats['realm_wins']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Achievements --}}
    <div class="card card-primary">
        <div class="card-header">
            <span class="card-title">Achievements</span>
        </div>
        <div class="card-body">
            @php $achievements = $user->achievements()->ordered()->get(); @endphp
            @if ($achievements->isEmpty())
                <p class="mb-0 text-muted">No achievements yet.</p>
            @else
                <div class="row gy-3">
                    @foreach ($achievements as $achievement)
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="card h-100 mb-0">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-4 text-center">
                                            <i class="ra {{ $achievement['icon'] }} text-muted" style="font-size: 48px; line-height: 1;"></i>
                                        </div>
                                        <div class="col-8">
                                            <div class="fw-bold">{{ $achievement['name'] }}</div>
                                            <div class="text-muted small">{{ $achievement['description'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Lifetime Ranking --}}
    <div class="card card-primary">
        <div class="card-header">
            <span class="card-title">Lifetime Ranking</span>
        </div>
        @if (!$dominions->isEmpty())
            <div class="card-body table-responsive">
                <div class="row">
                    @foreach ($leagues as $league)
                        @php $leagueDominions = $dominions->where('round.round_league_id', $league->id); @endphp
                        @if ($leagueDominions->isEmpty()) @continue @endif
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
                                    @foreach ($leagueDominions as $dominion)
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
                                            <td>{{ number_format($dailyRankings[$league->id]->where('rank', 1)->count() / $leagueDominions->count(), 2) }}</td>
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
            <div class="card-body">
                <p>No records found.</p>
            </div>
        @endif
    </div>
@endsection
