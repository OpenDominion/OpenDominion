@php
    use OpenDominion\Services\Dominion\SelectorService;
    $selectedDominion = app(SelectorService::class)->getUserSelectedDominion();
@endphp

<div class="navbar-tickers">
    @if ($selectedDominion && !$selectedDominion->round->hasStarted() && !$selectedDominion->round->hasEnded())
        <div class="ticker">
            <span class="ticker-lbl">Round Start</span>
            <span class="ticker-val" id="ticker-next-round" data-value="{{ $selectedDominion->round->start_date->format('Y-m-d H:i:s T') }}">00:00:00</span>
        </div>
    @else
        <div class="ticker">
            <span class="ticker-lbl">
                @if ($selectedDominion && !$selectedDominion->round->hasEnded())
                    Day <b>{{ $selectedDominion->round->daysInRound() }}</b> Hour <b>{{ $selectedDominion->round->hoursInDay() }}</b>
                @else
                    Server
                @endif
            </span>
            <span class="ticker-val" id="ticker-server">{{ date('H:i:s') }}</span>
        </div>
    @endif
</div>
