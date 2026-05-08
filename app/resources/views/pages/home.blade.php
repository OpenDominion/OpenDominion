<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>OpenDominion - A Free Online Strategy Game</title>

    <link rel="author" href="{{ asset('humans.txt') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:title" content="OpenDominion">
    <meta property="og:description" content="OpenDominion is a free online text-based multiplayer strategy game in a medieval fantasy setting." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ config('app.url') }}" />
    <meta property="og:image" content="{{ asset('assets/app/images/opendominion.png') }}" />

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1a1a1c">

    @include('partials.styles')

    <style>
        /* ── Landing page overrides ── */
        html, body {
            height: 100%;
            margin: 0;
            background: #1a1a1c;
            color: #e8e0d4;
        }

        .landing-wrapper {
            position: relative;
        }

        /* ── Navbar ── */
        .landing-nav {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(25, 25, 25, 0.45);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            border-bottom: 1px solid rgba(199, 176, 120, 0.35);
        }

        .landing-nav .navbar-brand {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: 1.35rem;
            color: #e8e0d4;
            letter-spacing: 0.03em;
            transition: color 0.2s;
        }

        .landing-nav .navbar-brand b {
            font-weight: 700;
            color: #afa170;
            transition: color 0.2s;
        }

        .landing-nav .navbar-brand:hover {
            color: #fff;
        }

        .landing-nav .navbar-brand:hover b {
            color: #c6b680;
        }

        .landing-nav .nav-link {
            color: rgba(232, 224, 212, 0.85);
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            transition: color 0.2s;
        }

        .landing-nav .nav-link:hover,
        .landing-nav .nav-link:focus {
            color: #afa170;
        }

        .landing-nav .navbar-toggler {
            border-color: rgba(199, 176, 120, 0.35);
        }

        .landing-nav .navbar-toggler-icon {
            filter: invert(0.8);
        }

        /* User dropdown overrides */
        .landing-nav .user-menu .user-image {
            width: 28px;
            height: 28px;
        }

        .landing-nav .user-menu > a span {
            color: rgba(232, 224, 212, 0.85);
        }

        .landing-nav .dropdown-menu {
            background: rgba(25, 25, 25, 0.95);
            border: 1px solid rgba(199, 176, 120, 0.35);
        }

        .landing-nav .dropdown-menu .dropdown-item,
        .landing-nav .dropdown-menu a {
            color: #e8e0d4;
        }

        .landing-nav .dropdown-menu .dropdown-item:hover {
            background: rgba(199, 176, 120, 0.15);
        }

        /* ── Hero ── */
        .landing-hero {
            position: relative;
            min-height: 50vh;
            padding-top: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .landing-hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 100vh;
            height: 100lvh; /* lock to largest viewport so URL bar toggle doesn't resize/zoom on mobile */
            z-index: 0;
            pointer-events: none;
            background: url('{{ asset('assets/app/images/battle-scene.png') }}') center top / cover no-repeat;
        }

        /* Dark overlay for readability */
        .landing-hero-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(20, 20, 22, 0.45);
            z-index: 1;
        }

        /* Vignette — fades edges into background color */
        .landing-hero-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 2;
            background: radial-gradient(
                ellipse 70% 65% at center,
                transparent 40%,
                rgba(26, 26, 28, 0.5) 70%,
                rgba(26, 26, 28, 0.9) 90%,
                #1a1a1c 100%
            );
        }

        /* ── Center content ── */
        .landing-content {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 2rem 1rem;
        }

        .landing-eyebrow {
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
            font-size: clamp(0.55rem, 1.6vw, 0.85rem);
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: #e8d4a0;
            -webkit-text-stroke: 1px #000;
            paint-order: stroke fill;
            margin-bottom: 0.9rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.95), 0 0 16px rgba(0, 0, 0, 0.85);
        }

        .landing-eyebrow::before,
        .landing-eyebrow::after {
            content: '';
            display: inline-block;
            width: clamp(1rem, 4vw, 2.5rem);
            height: 1px;
            background: rgba(199, 176, 120, 0.45);
            vertical-align: middle;
            margin: 0 clamp(0.4rem, 1.5vw, 1rem);
        }

        .landing-title {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            font-size: clamp(2rem, 5vw, 4.25rem);
            background: linear-gradient(
                180deg,
                #f8eed0 0%,
                #e8d4a0 40%,
                #b89863 78%,
                #8a6f3f 100%
            );
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            filter:
                drop-shadow(0 3px 6px rgba(0, 0, 0, 0.9))
                drop-shadow(0 8px 24px rgba(0, 0, 0, 0.55))
                drop-shadow(0 0 40px rgba(186, 159, 80, 0.25));
            letter-spacing: 0.06em;
            margin-bottom: 0.3rem;
            line-height: 1.1;
        }

        .landing-tagline {
            font-family: 'Cinzel', serif;
            font-weight: 600;
            font-size: clamp(1rem, 2.4vw, 1.85rem);
            color: #f0e6cc;
            text-shadow:
                0 2px 20px rgba(0, 0, 0, 0.7),
                0 0 60px rgba(186, 159, 80, 0.15);
            letter-spacing: 0.1em;
            margin-bottom: 1.4rem;
            line-height: 1.2;
        }

        .landing-subtitle {
            font-family: 'Crimson Pro', serif;
            font-size: clamp(0.85rem, 2.2vw, 1.4rem);
            color: rgba(232, 224, 212, 0.7);
            font-weight: 400;
            font-style: italic;
            letter-spacing: 0.04em;
            margin-bottom: 2rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9), 0 0 14px rgba(0, 0, 0, 0.75);
        }

        .landing-cta {
            display: inline-block;
            font-family: 'Cinzel', serif;
            font-weight: 600;
            font-size: clamp(1rem, 2vw, 1.25rem);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            padding: 0.85em 2.8em;
            color: #afa170;
            background: linear-gradient(
                180deg,
                rgba(186, 159, 80, 0.25) 0%,
                rgba(25, 25, 25, 0.4) 45%,
                rgba(25, 25, 25, 0.5) 55%,
                rgba(146, 119, 50, 0.2) 100%
            );
            border: 1.5px solid rgba(199, 176, 120, 0.5);
            border-top-color: rgba(216, 189, 100, 0.6);
            border-bottom-color: rgba(166, 139, 60, 0.45);
            border-radius: 3px;
            text-decoration: none;
            transition: all 0.25s ease;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .landing-cta:hover,
        .landing-cta:focus {
            color: #c6b680;
            background: linear-gradient(
                180deg,
                rgba(206, 179, 90, 0.35) 0%,
                rgba(25, 25, 25, 0.55) 45%,
                rgba(25, 25, 25, 0.6) 55%,
                rgba(166, 139, 60, 0.3) 100%
            );
            border-color: rgba(207, 177, 76, 0.65);
            box-shadow: 0 0 30px rgba(207, 177, 76, 0.15), inset 0 0 20px rgba(207, 177, 76, 0.05);
            text-decoration: none;
        }

        /* ── Cards section ── */
        .landing-cards {
            position: relative;
            z-index: 10;
            padding: 3rem 0 4rem;
        }

        .landing-card {
            background: rgba(25, 25, 25, 0.45);
            border: 1px solid rgba(199, 176, 120, 0.35);
            border-radius: 4px;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            margin-bottom: 1.5rem;
        }

        .landing-card-small {
            display: flex;
            flex-direction: column;
        }

        .landing-card-small .landing-card-body {
            flex: 1;
        }

        .landing-card-tall {
            display: flex;
            flex-direction: column;
        }

        .landing-card-tall .landing-card-body {
            flex: 1;
        }

        @media (min-width: 992px) {
            .landing-cards .col-lg-3,
            .landing-cards .col-lg-6 {
                display: flex;
                flex-direction: column;
            }

            .landing-card-small {
                height: calc(60% - 2rem);
            }

            .landing-card-small:last-child {
                margin-bottom: 0;
            }

            .landing-card-tall {
                height: 100%;
                margin-bottom: 0;
            }
        }

        .landing-card-header {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid rgba(199, 176, 120, 0.35);
            font-family: 'Cinzel', serif;
            font-weight: 600;
            font-size: 1rem;
            color: #afa170;
            letter-spacing: 0.03em;
        }

        .landing-card-body {
            padding: 1.25rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.92rem;
            color: rgba(232, 224, 212, 0.8);
            line-height: 1.6;
        }

        .landing-card-body p:last-child {
            margin-bottom: 0;
        }

        .landing-card table {
            width: 100%;
            margin: 0;
            color: rgba(232, 224, 212, 0.8);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
        }

        .landing-card table td {
            padding: 0.45rem 0.75rem;
            border-top: 1px solid rgba(199, 176, 120, 0.2);
        }

        .landing-card table tr:first-child td {
            border-top: none;
        }

        .landing-card .stat-label {
            color: rgba(232, 224, 212, 0.55);
        }

        .landing-card .stat-value {
            color: #e8e0d4;
            font-weight: 500;
        }

        .landing-card .rank-number {
            color: rgba(207, 177, 76, 0.7);
            font-weight: 600;
            min-width: 1.5em;
            display: inline-block;
        }

        .landing-card .rank-change-up { color: #6daa6d; }
        .landing-card .rank-change-down { color: #c46b6b; }
        .landing-card .rank-change-same { color: rgba(232, 224, 212, 0.35); }

        /* Link cards */
        a.landing-card {
            display: flex;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        a.landing-card:hover {
            border-color: rgba(199, 176, 120, 0.5);
            background: rgba(25, 25, 25, 0.6);
            box-shadow: 0 0 20px rgba(207, 177, 76, 0.08);
        }

        a.landing-card .landing-card-header {
            transition: color 0.25s ease;
        }

        a.landing-card .landing-card-body {
            text-align: justify;
            hyphens: auto;
        }

        a.landing-card:hover .landing-card-header {
            color: #c6b680;
        }

        a.landing-card .card-icon {
            font-size: 1.5rem;
            color: rgba(207, 177, 76, 0.5);
            margin-bottom: 0.75rem;
        }

        .landing-status {
            font-family: 'Cinzel', serif;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.5rem 0;
        }

        .landing-status.text-active { color: #6daa6d; }
        .landing-status.text-registration { color: #afa170; }
        .landing-status.text-inactive { color: #c46b6b; }

        /* ── Collapsed nav bg on mobile ── */
        @media (max-width: 991.98px) {
            .landing-nav .navbar-collapse {
                background: rgba(25, 25, 25, 0.95);
                padding: 0.5rem 1rem;
                border-radius: 0 0 6px 6px;
            }
        }
    </style>

    @include('partials.analytics')
</head>
<body>

<div class="landing-wrapper">

    <!-- Navbar -->
    <nav class="landing-nav navbar navbar-expand-lg py-2">
        <div class="container">

            <a href="{{ url('') }}" class="navbar-brand">Open<b>Dominion</b></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landing-nav-collapse" aria-controls="landing-nav-collapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="landing-nav-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a href="{{ route('about') }}" class="nav-link">About</a></li>
                    <li class="nav-item"><a href="{{ route('user-agreement') }}" class="nav-link">Rules</a></li>
                    <li class="nav-item"><a href="{{ route('scribes.overview') }}" class="nav-link">Scribes</a></li>
                    <li class="nav-item"><a href="{{ route('valhalla.index') }}" class="nav-link">Valhalla</a></li>
                    @include('partials.wiki-nav')
                    @auth
                        <li class="nav-item"><a href="{{ $playUrl }}" class="nav-link"><b>{{ $playLabel }}</b></a></li>
                    @endauth
                </ul>

                <ul class="navbar-nav ms-auto">
                    @if ($discordInviteLink = config('app.discord_invite_link'))
                        <li class="nav-item">
                            <a href="{{ $discordInviteLink }}" target="_blank" class="nav-link" title="Join us on Discord" aria-label="Join us on Discord">
                                <i class="fab fa-discord"></i>
                            </a>
                        </li>
                    @endif
                    @auth
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link">
                                <img src="{{ Auth::user()->getAvatarUrl() }}" class="rounded-circle" style="width: 24px; height: 24px;" alt="{{ Auth::user()->display_name }}">
                                <span class="d-none d-md-inline-block ms-2">{{ Auth::user()->display_name }}</span>
                            </a>
                        </li>
                    @else
                        <li class="nav-item"><a href="{{ route('auth.register') }}" class="nav-link">Register</a></li>
                        <li class="nav-item"><a href="{{ route('auth.login') }}" class="nav-link">Login</a></li>
                    @endauth
                </ul>
            </div>

        </div>
    </nav>

    <!-- Hero -->
    <div class="landing-hero">
        <div class="landing-hero-bg"></div>

        <div class="landing-content">
            <p class="landing-eyebrow">Based on the Kamikaze Games Classic</p>
            <h1 class="landing-title">DominioN</h1>
            <p class="landing-tagline">Where Power Prevails</p>
            <p class="landing-subtitle">Wage war in a text-based medieval fantasy strategy game.</p>
            <a href="{{ $playUrl }}" class="landing-cta">Play Now</a>
        </div>
    </div>

    <!-- Cards Section -->
    <div class="landing-cards">
        <div class="container">
            <div class="row">

                {{-- Left column: Round status + Podcast --}}
                <div class="col-lg-3">

                    {{-- Current Round --}}
                    <div class="landing-card landing-card-small">
                        <div class="landing-card-header">
                            @if ($currentRound === null)
                                <i class="fa fa-shield-halved fa-fw me-2"></i> Current Round
                            @else
                                <i class="fa fa-shield-halved fa-fw me-2"></i> Round #{{ $currentRound->number }}
                            @endif
                        </div>
                        <div class="landing-card-body text-center">
                            @if ($currentRound === null || $currentRound->hasEnded())
                                <p class="landing-status text-inactive">Inactive</p>
                                <p>There is no ongoing round.</p>
                                @if ($discordInviteLink = config('app.discord_invite_link'))
                                    <p>Check the <a href="{{ $discordInviteLink }}" target="_blank" style="color: #afa170;">Discord server</a> for more information.</p>
                                @endif
                            @else
                                @if ($currentRound->realmAssignmentDate() > now())
                                    <p class="landing-status text-registration">Open for Registration</p>
                                    <p>Pack deadline in {{ $currentRound->timeUntilRealmAssignment() }}.</p>
                                    <p>Starts in {{ $currentRound->timeUntilStart() }}, lasts {{ $currentRound->durationInDays() }} days.</p>
                                @elseif ($currentRound->start_date > now())
                                    <p class="landing-status text-registration">Starting Soon</p>
                                    <p>Individual registration still open!</p>
                                    <p>Starts in {{ $currentRound->timeUntilStart() }}, lasts {{ $currentRound->durationInDays() }} days.</p>
                                @else
                                    <p class="landing-status text-active p-0">Active</p>
                                @endif
                            @endif
                        </div>
                        @if ($currentRound !== null && !$currentRound->hasEnded())
                            <div class="landing-card-body pt-0 mb-3">
                                <table>
                                    <tr>
                                        <td class="stat-label"><i class="fa fa-clock fa-fw me-2"></i>Day</td>
                                        <td class="text-end stat-value">{{ number_format($currentRound->daysInRound()) }} / {{ number_format($currentRound->durationInDays()) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="stat-label"><i class="fa fa-users fa-fw me-2"></i>Players</td>
                                        <td class="text-end stat-value">{{ number_format($currentRound->dominions->where('user_id', '!=', null)->count()) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="stat-label"><i class="fa fa-flag fa-fw me-2"></i>Realms</td>
                                        <td class="text-end stat-value">{{ number_format($currentRound->realms->count() - 1) }}</td>
                                    </tr>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Podcast --}}
                    <a href="https://anchor.fm/riol-talk" target="_blank" class="landing-card landing-card-small">
                        <div class="landing-card-header">
                            <i class="fa fa-podcast fa-fw me-2"></i> Podcasts
                        </div>
                        <div class="landing-card-body">
                            <p>Strategy discussion, round recaps, and community interviews from the world of OpenDominion.</p>
                        </div>
                    </a>

                </div>

                {{-- Center column: Rankings --}}
                <div class="col-lg-6">
                    <div class="landing-card landing-card-tall">
                        <div class="landing-card-header">
                            @if ($currentRound !== null)
                                {{ $currentRound->hasStarted() && !$currentRound->hasEnded() ? 'Current' : 'Previous' }} Round Rankings
                            @else
                                Round Rankings
                            @endif
                        </div>
                        <div class="landing-card-body">
                            @if ($currentRankings !== null && !$currentRankings->isEmpty())
                                <table>
                                    @foreach ($currentRankings as $row)
                                        <tr>
                                            <td style="width: 2.5rem;"><span class="rank-number">{{ $row->rank }}</span></td>
                                            <td>{{ $row->dominion_name }} <span class="stat-label">(#{{ $row->realm_number }})</span></td>
                                            <td class="text-end stat-value">{{ number_format($row->value) }}</td>
                                            <td class="text-end" style="width: 3.5rem;">
                                                @php $rankChange = (int) ($row->previous_rank - $row->rank); @endphp
                                                @if ($rankChange > 0)
                                                    <span class="rank-change-up"><i class="fa fa-caret-up"></i> {{ $rankChange }}</span>
                                                @elseif ($rankChange === 0)
                                                    <span class="rank-change-same">-</span>
                                                @else
                                                    <span class="rank-change-down"><i class="fa fa-caret-down"></i> {{ abs($rankChange) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <p class="text-center" style="color: rgba(232, 224, 212, 0.45);">No rankings recorded yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Right column: Valhalla + Scribes links --}}
                <div class="col-lg-3">

                    <a href="{{ route('valhalla.index') }}" class="landing-card landing-card-small">
                        <div class="landing-card-header">
                            <i class="fa fa-trophy fa-fw me-2"></i> Valhalla
                        </div>
                        <div class="landing-card-body">
                            <p>The hall of legends. Browse the all-time leaderboards. See who conquered, who endured, and whose dominion stood above all others.</p>
                        </div>
                    </a>

                    <a href="{{ route('scribes.overview') }}" class="landing-card landing-card-small">
                        <div class="landing-card-header">
                            <i class="fa fa-book fa-fw me-2"></i> The Scribes
                        </div>
                        <div class="landing-card-body">
                            <p>Knowledge is power. Learn about the races, units, buildings, spells, and everything else at your disposal.</p>
                        </div>
                    </a>

                </div>

            </div>
        </div>
    </div>

</div>

@include('partials.scripts')

</body>
</html>
