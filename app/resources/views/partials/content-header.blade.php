@php
    use Carbon\Carbon;
    use OpenDominion\Services\Dominion\SelectorService;
    $selectedDominion = $selectedDominion ?? app(SelectorService::class)->getUserSelectedDominion();
    $secondsUntilStart = 0;
    if ($selectedDominion && $selectedDominion->round) {
        $roundStart = $selectedDominion->round->start_date;
        if ($roundStart > Carbon::now()) {
            $secondsUntilStart = (int) $roundStart->diffInSeconds(Carbon::now());
        }
    }
@endphp

<div class="content-header-bar">
    <div class="content-header-left">
        @hasSection('page-header')
            <span class="content-header-title">@yield('page-header')</span>
        @endif
        @hasSection('page-subheader')
            <span class="content-header-sub">@yield('page-subheader')</span>
        @endif
    </div>
    <div class="content-header-right">
        @if ($selectedDominion && $selectedDominion->round && !$selectedDominion->round->hasEnded())
            @if ($secondsUntilStart > 0)
                <span class="content-header-meta">Round starts <span id="ticker-next-round" data-value="{{ $roundStart->format('Y-m-d H:i:s T') }}">00:00:00</span></span>
            @else
                <span class="content-header-meta">Day {{ $selectedDominion->round->daysInRound() }}</span>
                <span class="content-header-sep">&middot;</span>
                <span class="content-header-meta">Hour {{ $selectedDominion->round->hoursInDay() }}</span>
                <span class="content-header-sep">&middot;</span>
                <span class="content-header-meta">Tick <span id="ticker-next-tick">00:00:00</span></span>
            @endif
        @endif
        <span class="content-header-sep">&middot;</span>
        <span class="content-header-meta content-header-clock" id="ticker-server">{{ date('H:i:s') }}</span>
    </div>
</div>
