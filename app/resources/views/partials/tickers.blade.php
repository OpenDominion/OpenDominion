@php
    use Carbon\Carbon;
    use OpenDominion\Services\Dominion\SelectorService;
    $selectedDominion = app(SelectorService::class)->getUserSelectedDominion();

    if($selectedDominion) {
        $round = $selectedDominion->round;
        $secondsUntilStart = $round->start_date->diffInSeconds(Carbon::now());
    }
@endphp
<div class="pull-right">
    <span class="badge">
        Server: <span id="ticker-server">{{ date('H:i:s') }}</span>
    </span>
    <span class="badge">
        @if($secondsUntilStart > 0)
            Round: <span id="ticker-next-round" data-value="{{ $round->start_date }}">00:00:00</span>
        @else
            Tick: <span id="ticker-next-tick">00:00:00</span>
        @endif
    </span>
</div>
