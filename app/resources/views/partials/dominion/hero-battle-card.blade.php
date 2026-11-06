@php
    $stats = $view->stats($combatant);
    $effects = $view->effects($combatant);
    $model = $view->model($combatant);
    $shield = $view->shield($combatant);
    $maxHealth = max(1, $stats['health']['value']);
    $healthPercent = min(100, $combatant->currentHealth / $maxHealth * 100);
    $controlled = $view->isControlled($combatant);
    $cooldowns = collect($view->abilities($combatant))->pluck('cooldown', 'key');
    $plan = $controlled && $view->canQueueAhead() ? $view->queue($combatant) : [];
    $abbreviations = ['attack' => 'ATK', 'defense' => 'DEF', 'evasion' => 'EVA', 'focus' => 'FOC', 'counter' => 'CTR', 'recover' => 'REC'];
@endphp
<div @class(['battle-card', 'defeated' => !$combatant->isAlive()])>
    <div class="battle-card-head">
        <span class="battle-card-medal"><i class="ra {{ $view->icon($combatant) }}"></i></span>
        <span class="battle-card-id">
            <span class="battle-card-name">
                {{ $combatant->name }}
                @if ($view->isBorrowed($combatant))
                    <span class="battle-card-tag">Borrowed</span>
                @elseif ($view->isAlly($combatant))
                    <span class="battle-card-tag">Ally</span>
                @elseif ($controlled)
                    <span class="battle-card-tag battle-card-tag-you">You</span>
                @endif
                @if (!$combatant->isAlive())
                    <i class="ra ra-skull" title="Defeated" data-bs-toggle="tooltip"></i>
                @endif
            </span>
            @if ($model?->hero)
                <span class="battle-card-sub">{{ app(\OpenDominion\Helpers\HeroHelper::class)->getClassDisplayName($model->hero->class) }}</span>
            @endif
        </span>
        @if ($change !== 0)
            <span @class(['battle-card-delta', 'text-green' => $change > 0, 'battle-card-delta-loss' => $change < 0])>{{ $change > 0 ? '+' : '−' }}{{ abs($change) }}</span>
        @endif
        @if ($combatant->isPiloted() && !$controlled && $model && !$view->model->finished)
            <span class="battle-card-clock" title="Time remaining to act" data-bs-toggle="tooltip"><i class="ra ra-hourglass"></i> {{ rfloor($model->timeLeft() / 3600) }}h {{ rfloor($model->timeLeft() % 3600 / 60) }}m</span>
        @endif
    </div>

    <div class="battle-hp" title="{{ $stats['health']['tooltip'] }}" data-bs-toggle="tooltip">
        <div @class(['battle-hp-fill', 'mid' => $healthPercent <= 60 && $healthPercent > 30, 'low' => $healthPercent <= 30]) style="width: {{ round($healthPercent, 1) }}%"></div>
        @if ($shield > 0)
            <div class="battle-hp-shield" style="left: {{ round($healthPercent, 1) }}%; width: {{ round(min(100 - $healthPercent, $shield / $maxHealth * 100), 1) }}%"></div>
        @endif
        <span>{{ $combatant->currentHealth }}@if ($shield > 0) + {{ $shield }}@endif / {{ $maxHealth }}</span>
    </div>

    <div class="battle-stats">
        @foreach ($abbreviations as $stat => $abbreviation)
            <div @class(['battle-stat', 'cooldown' => ($cooldowns[$stat] ?? 0) > 0]) title="{{ $stats[$stat]['label'] }}: {{ $stats[$stat]['tooltip'] }}@if (($cooldowns[$stat] ?? 0) > 0) ({{ $stats[$stat]['label'] }} is on cooldown)@endif" data-bs-toggle="tooltip">
                <div class="battle-stat-label">{{ $abbreviation }}</div>
                <div @class(['battle-stat-value', 'text-green' => $stats[$stat]['value'] > $stats[$stat]['base'], 'text-red' => $stats[$stat]['value'] < $stats[$stat]['base']])>{{ $stats[$stat]['value'] }}</div>
            </div>
        @endforeach
    </div>

    @if ($effects)
        <div class="battle-effects">
            @foreach ($effects as $effect)
                @php
                    $badge = $effect['active'] ? 'bg-success' : match ($effect['kind']) {
                        'buff' => 'bg-success',
                        'debuff' => 'bg-danger',
                        'innate' => 'bg-secondary',
                        default => 'bg-info',
                    };
                @endphp
                <span class="badge {{ $badge }}" data-bs-toggle="tooltip" title="{{ $effect['description'] }}">{{ $effect['name'] }}@if ($effect['stacks'] > 1) x{{ $effect['stacks'] }}@endif @if ($effect['turns'] !== null)({{ $effect['turns'] }})@endif</span>
            @endforeach
        </div>
    @endif

    @if ($plan)
        <div class="battle-card-foot">
            <span class="battle-card-foot-label">Plan</span>
            @foreach ($plan as $queued)
                <span class="battle-card-chip"><span>T{{ $queued['turn'] }}</span> {{ $queued['ability'] }}@if ($queued['target']) → {{ $queued['target'] }}@endif</span>
            @endforeach
        </div>
    @elseif (!$controlled && $combatant->lastAction !== null && $combatant->isAlive())
        <div class="battle-card-foot">Last turn: {{ $view->abilityName($combatant->lastAction) }}@foreach ($view->warnings($combatant) as $warning) · <span class="text-warning">{{ $warning }}</span>@endforeach</div>
    @endif
</div>
