@php
    use Carbon\Carbon;
    use OpenDominion\Services\Dominion\SelectorService;
    $selectedDominion = app(SelectorService::class)->getUserSelectedDominion();
    $secondsUntilStart = 0;
    if ($selectedDominion) {
        $roundStart = $selectedDominion->round->start_date;
        if ($roundStart > Carbon::now()) {
            $secondsUntilStart = $roundStart->diffInSeconds(Carbon::now());
        }
    }
@endphp

<div class="pull-right">
    <span class="badge">
        Server: <span id="ticker-server">{{ date('H:i:s') }}</span>
    </span>
    @if ($selectedDominion && !$selectedDominion->round->hasEnded())
        <span class="badge">
            @if($secondsUntilStart > 0)
                Round: <span id="ticker-next-round" data-value="{{ $roundStart->format('Y-m-d H:i:s T') }}">00:00:00</span>
            @else
                Tick: <span id="ticker-next-tick">00:00:00</span>
            @endif
        </span>
    @endif
</div>
<div class="clearfix"></div>
