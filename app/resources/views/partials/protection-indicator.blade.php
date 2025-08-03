@if (isset($selectedDominion) && $protectionService->isUnderProtection($selectedDominion))
    <div class="alert-info text-center" style="padding: 10px 15px; font-size: 13px;">
        <div class="row">
            <div class="col-xs-2 text-left">
                @if ($selectedDominion->protection_ticks_remaining <= $selectedDominion->protection_ticks)
                    <a href="{{ route('dominion.misc.undo-tick') }}" class="btn btn-xs btn-danger disable-after-click" style="margin-right: 20px;">
                        &laquo; Undo
                    </a>
                @else
                    <a href="{{ route('dominion.protection.import-log') }}" class="btn btn-xs btn-primary" style="margin-right: 20px;">
                        Import
                    </a>
                @endif
            </div>
            <div class="col-xs-8 text-center">
                <i class="icon ra ra-shield"></i>
                @if ($selectedDominion->isBuildingPhase())
                    <a href="{{ route('dominion.protection.buildings') }}">Starting Building Phase</a>
                @else
                    Protection Hour {{ $selectedDominion->protection_ticks - $selectedDominion->protection_ticks_remaining + 1 }}
                @endif
                @if (!$selectedDominion->isBuildingPhase())
                    <span class="text-nowrap">
                        @if ($selectedDominion->protection_ticks_remaining > 0)
                            @if ($selectedDominion->protection_type !== 'quick' || $selectedDominion->protection_ticks_remaining <= 12)
                                ({{ $selectedDominion->protection_ticks_remaining }} ticks remaining)
                            @endif
                        @else
                            - <b>{{ $protectionService->getUnderProtectionHoursLeft($selectedDominion) }}</b> hours remaining
                        @endif
                    </span>
                @endif
            </div>
            <div class="col-xs-2 text-right">
                @if (!$selectedDominion->protection_finished)
                    <a href="{{ route('dominion.misc.tick') }}" class="btn btn-xs btn-primary disable-after-click">
                        Next &raquo;
                    </a>
                @endif
            </div>
        </div>
        @if ($selectedDominion->protection_ticks_remaining > $selectedDominion->protection_ticks)
            <div class="row">
                <div class="col-md-12 text-center">
                    It's time to choose your starting buildings.
                    <br/>Don't forget to build docks if you plan on attacking.
                </div>
            </div>
        @endif
        @if ($selectedDominion->protection_type == 'quick')
            @if ($selectedDominion->protection_ticks_remaining == 36)
                <div class="row">
                    <div class="col-md-12 text-center">
                        Now you can choose to take daily bonuses, exchange resources, or explore.
                        <br/>Next, you'll skip ahead 12 ticks.
                    </div>
                </div>
            @endif
            @if ($selectedDominion->protection_ticks_remaining == 24)
                <div class="row">
                    <div class="col-md-12 text-center">
                        This is your last chance to build smithies or anything else you need prior to training troops.
                        <br/>Don't forget to cast spells.
                        <br/>Next, you'll skip ahead 12 ticks one more time.
                    </div>
                </div>
            @endif
            @if ($selectedDominion->protection_ticks_remaining == 12)
                <div class="row">
                    <div class="col-md-12 text-center">
                        This is your only chance to build or train elites, spies, and wizards to coincide with leaving protection.
                        <br/>Don't forget to cast spells again.
                        <br/>Next, you'll perform each remaining tick individually.
                    </div>
                </div>
            @endif
            @if ($selectedDominion->protection_ticks_remaining == 9)
                <div class="row">
                    <div class="col-md-12 text-center">
                        This is your last chance to train specialists to coincide with leaving protection.
                        <!--<br/>You'll need at least {{ number_format(4580) }} defense to continue at this land size.-->
                    </div>
                </div>
            @endif
            @if ($selectedDominion->protection_ticks_remaining == 0 && !$selectedDominion->protection_finished)
                <div class="row">
                    <div class="col-md-12 text-center">
                        This is your last change to go back and change anything before leaving protection.
                        <br/>Don't forget to select a hero.
                    </div>
                </div>
            @endif
        @endif
    </div>
@endif
