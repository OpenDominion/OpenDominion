@php
    use Carbon\Carbon;
    use OpenDominion\Services\Dominion\SelectorService;
    $selectedDominion = app(SelectorService::class)->getUserSelectedDominion();
    $secondsUntilStart = 0;
    if ($selectedDominion) {
        $roundStart = $selectedDominion->round->start_date;
        if ($roundStart > Carbon::now()) {
            $secondsUntilStart = (int) $roundStart->diffInSeconds(Carbon::now());
        }
    }
@endphp

<div class="navbar-tickers">
    <div class="ticker">
        <span class="ticker-lbl">Server</span>
        <span class="ticker-val" id="ticker-server">{{ date('H:i:s') }}</span>
    </div>
    @if ($selectedDominion && !$selectedDominion->round->hasEnded())
        <span class="ticker-dot">&bull;</span>
        <div class="ticker">
            @if ($secondsUntilStart > 0)
                <span class="ticker-lbl">Round</span>
                <span class="ticker-val" id="ticker-next-round" data-value="{{ $roundStart->format('Y-m-d H:i:s T') }}">00:00:00</span>
            @else
                <span class="ticker-lbl">Tick</span>
                <span class="ticker-val" id="ticker-next-tick">00:00:00</span>
            @endif
        </div>
    @endif
</div>
