@if (isset($selectedDominion) && !Route::is(['home', 'valhalla*', 'scribes*', 'user-agreement']))
    @if ($selectedDominion->isLocked())
        <div class="alert alert-warning">
            @if ($selectedDominion->locked_at !== null)
                <p><i class="fa fa-warning me-1"></i> This dominion is <strong>locked</strong> due to a rules violation. No actions can be performed and no ticks will be processed.</p>
            @elseif ($selectedDominion->abandoned_at !== null && $selectedDominion->abandoned_at < now())
                <p><i class="fa fa-warning me-1"></i> This dominion is <strong>locked</strong> due to abandonment. No actions can be performed and no ticks will be processed.</p>
            @else
                <p>
                    <i class="fa fa-warning me-1"></i> This dominion is <strong>locked</strong> due to the round having ended. No actions can be performed and no ticks will be processed.<br/>
                    Go to your <a href="{{ route('dashboard') }}">dashboard</a> to check if new rounds are open to play.
                </p>
            @endif
        </div>
    @elseif (now()->diffInHours($selectedDominion->round->end_date) < 24)
        <div class="alert alert-warning">
            <p><i class="fa fa-warning me-1"></i> The round will end in {{ now()->longAbsoluteDiffForHumans($selectedDominion->round->end_date, 2) }}.
                @if ($selectedDominion->round->offensiveActionsAreEnabledButCanBeDisabled())
                    Offensive actions can be disabled at any time.
                @elseif ($selectedDominion->round->hasOffensiveActionsDisabled())
                    Offensive actions have been disabled.
                @endif
            </p>
        </div>
    @endif

    @if (!$selectedDominion->round->hasAssignedRealms() && !$selectedDominion->round->hasStarted())
        <div class="alert alert-warning">
            <p><i class="fa fa-warning me-1"></i> The round has not yet started, but you can simulate your protection in advance. Realms will be assigned in {{ $selectedDominion->round->timeUntilRealmAssignment() }}, after which you will have 4 days to coordinate with your realm before the round starts.</p>
        </div>
    @endif

    @if ($selectedDominion->ai_enabled)
        <div class="alert alert-info">
            <p><i class="ra ra-robot-arm me-1"></i> You have <a href="{{ route('dominion.bonuses.actions') }}">automated actions</a> scheduled in {{ hours_until_next_action($selectedDominion->ai_config, $selectedDominion->round->getTick()) }} tick(s).</p>
        </div>
    @endif

@endif

@if (!Route::is(['home', 'round.calendar']))
    @php
        $selectedDominionRoundEnded = isset($selectedDominion) && $selectedDominion->round->hasEnded();
        $noSelectedDominion = !isset($selectedDominion);
        $showNextRoundBanner = ($selectedDominionRoundEnded || $noSelectedDominion) && !\OpenDominion\Models\Round::active()->exists();
    @endphp
    @if ($showNextRoundBanner)
        @php($nextRound = \OpenDominion\Models\Round::upcoming()->first())
        @if ($nextRound)
            <div class="alert alert-info">
                <p class="mb-0">
                    <i class="ra ra-wooden-sign me-1"></i>
                    The next round, <a href="{{ route('round.calendar') }}" class="alert-link"><strong>{{ $nextRound->name }}</strong></a>, starts {{ $nextRound->start_date->format('M j, Y') }} (in {{ $nextRound->timeUntilStart() }}).
                    @if ($nextRound->description) — {{ $nextRound->description }} @endif
                </p>
            </div>
        @endif
    @endif
@endif

@if (!$errors->isEmpty())
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <h4>One or more errors occurred:</h4>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@foreach (['danger', 'warning', 'success', 'info'] as $alert_type)
    @if (Session::has('alert-' . $alert_type))
        <div class="alert alert-{{ $alert_type }} alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <p>{{ Session::get('alert-' . $alert_type) }}</p>
        </div>
    @endif
@endforeach
