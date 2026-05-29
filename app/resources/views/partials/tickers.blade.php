@php
    use OpenDominion\Services\Dominion\SelectorService;
    $selectedDominion = app(SelectorService::class)->getUserSelectedDominion();
@endphp

<div class="navbar-tickers">
    @if ($selectedDominion && !$selectedDominion->round->hasStarted())
        <div class="ticker">
            <span class="ticker-lbl">Round Start</span>
            <span class="ticker-val" id="ticker-next-round" data-value="{{ $selectedDominion->round->start_date->format('Y-m-d H:i:s T') }}">00:00:00</span>
        </div>
    @else
        <div class="ticker">
            <span class="ticker-lbl">
                @if ($selectedDominion && $selectedDominion->round->isActive())
                    <span class="ticker-lbl-full">Day <b>{{ $selectedDominion->round->daysInRound() }}</b> Hour <b>{{ $selectedDominion->round->hoursInDay() }}</b></span>
                    <span class="ticker-lbl-short">D<b>{{ $selectedDominion->round->daysInRound() }}</b> H<b>{{ $selectedDominion->round->hoursInDay() }}</b></span>
                @else
                    Server
                @endif
            </span>
            <span class="ticker-val" id="ticker-server" data-round-active="{{ $selectedDominion && $selectedDominion->round->isActive() ? '1' : '0' }}">{{ date('H:i:s') }}</span>
        </div>
    @endif
</div>
