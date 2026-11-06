@php
    $battle = $view->model;
    $headline = $view->headline();
    $sides = $view->sides();
    $commandable = $showOrders ? $view->commandable() : [];
    $changes = $view->healthChanges();
    $timeLeft = $view->timeLeft();
    $waitingOn = $battle->finished ? [] : $view->waitingOn();
    $turns = $view->turns();
@endphp
<div class="hero-battle">
    <div class="battle-bar">
        <div>
            <div class="battle-bar-title">{{ $headline['title'] }}</div>
            <div class="battle-bar-meta">
                <span class="battle-bar-mode">{{ $headline['mode'] }}</span>
                @if ($showOrders && $view->isWaitingOnYou())
                    <span class="battle-bar-status fw-bold" data-waiting-on-you><span class="battle-bar-dot"></span>Waiting for you to lock in</span>
                @elseif ($waitingOn)
                    <span class="battle-bar-status"><span class="battle-bar-dot"></span>Waiting for {{ implode(' and ', $waitingOn) }}</span>
                @endif
            </div>
        </div>
        <div class="battle-bar-turn">
            @if ($battle->finished)
                <b>{{ $battle->isDraw() ? 'Draw!' : $battle->winnerLabel() . ($battle->winningCombatants()->whereNotNull('hero_id')->count() > 1 ? ' win!' : ' wins!') }}</b>
                <div>after {{ $battle->current_turn }} {{ \Illuminate\Support\Str::plural('turn', $battle->current_turn) }}</div>
            @else
                <b>Turn {{ $battle->current_turn }}</b>
                @if ($timeLeft !== null)
                    <div><i class="ra ra-hourglass"></i> {{ rfloor($timeLeft / 3600) }}h {{ rfloor($timeLeft % 3600 / 60) }}m to act</div>
                @endif
            @endif
        </div>
    </div>

    <div class="battle-layout">
        <div class="battle-arena">
            <div class="battle-sides">
                @foreach ($sides as $side)
                    <div @class(['battle-side', 'battle-side-ally' => $loop->first, 'battle-side-compact' => count($side['combatants']) >= 3])>
                        <div class="battle-side-label"><i class="ra {{ $loop->first ? 'ra-player' : 'ra-crossed-swords' }}"></i> {{ $side['label'] }}</div>
                        <div class="battle-side-cards">
                            @foreach ($side['combatants'] as $combatant)
                                @include('partials.dominion.hero-battle-card', ['view' => $view, 'combatant' => $combatant, 'change' => $changes[$combatant->id] ?? 0])
                            @endforeach
                        </div>
                    </div>
                    @if ($loop->first && count($sides) === 2)
                        <div class="battle-vs">VS</div>
                    @endif
                @endforeach
            </div>
        </div>

        @if ($commandable)
            <div class="battle-orders">
                @include('partials.dominion.hero-battle-orders', ['view' => $view, 'commandable' => $commandable])
            </div>
        @endif

        <aside class="battle-log">
            @include('partials.dominion.hero-turn-summary', ['view' => $view, 'turns' => $turns])
        </aside>
    </div>
</div>
