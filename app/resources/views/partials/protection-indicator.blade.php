@if (isset($selectedDominion) && $protectionService->isUnderProtection($selectedDominion))
    <div class="alert alert-info small text-center border-0 rounded-0 mb-0">
        <div class="row g-2 align-items-center">
            <div class="col-2 text-center text-sm-start">
                @if ($selectedDominion->protection_ticks_remaining <= $selectedDominion->protection_ticks)
                    <a href="{{ route('dominion.misc.undo-tick') }}" class="btn btn-sm btn-danger py-0 disable-after-click">
                        &laquo; Undo
                    </a>
                @else
                    <a href="{{ route('dominion.protection.import-log') }}" class="btn btn-sm btn-primary py-0">
                        Import
                    </a>
                @endif
            </div>
            <div class="col-8 text-center">
                <i class="icon ra ra-shield"></i>
                @if ($selectedDominion->isBuildingPhase())
                    <a href="{{ route('dominion.protection.buildings') }}">Starting Building Phase</a>
                @else
                    Protection Hour {{ $selectedDominion->protection_ticks - $selectedDominion->protection_ticks_remaining + 1 }}
                @endif
            </div>
            <div class="col-2 text-center text-sm-end">
                @if (!$selectedDominion->protection_finished)
                    <a href="{{ route('dominion.misc.tick') }}" class="btn btn-sm btn-primary py-0 disable-after-click">
                        @if ($selectedDominion->protection_ticks_remaining == 0)
                            Confirm
                        @else
                            Next &raquo;
                        @endif
                    </a>
                @endif
            </div>
        </div>
        @if (!$selectedDominion->isBuildingPhase())
            @if ($selectedDominion->protection_ticks_remaining > 0)
                @if ($selectedDominion->protection_type !== 'quick' || $selectedDominion->protection_ticks_remaining <= 12)
                    <div class="row">
                        <div class="col-12 text-center">
                            <span class="text-nowrap">({{ $selectedDominion->protection_ticks_remaining }} ticks remaining)</span>
                        </div>
                    </div>
                @endif
            @else
                <div class="row">
                    <div class="col-12 text-center">
                        <span class="text-nowrap"><b>{{ $protectionService->getUnderProtectionHoursLeft($selectedDominion) }}</b> hours remaining</span>
                    </div>
                </div>
            @endif
        @endif
        @if ($selectedDominion->protection_ticks_remaining > $selectedDominion->protection_ticks)
            <div class="row">
                <div class="col-md-12 text-center">
                    It's time to choose your starting buildings.
                    <br/>Don't forget to build docks if you plan on attacking (and your units requires boats).
                </div>
            </div>
        @endif
        @if ($selectedDominion->protection_type == 'quick')
            @if ($selectedDominion->protection_ticks_remaining == 36)
                <div class="row">
                    <div class="col-md-12 text-center">
                        Now you can choose to explore, take daily bonuses, or exchange resources.
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
                        @if ($selectedDominion->round->daysInRound() > 1)
                            <b>Late Start Bonus</b>: Defensive troops have been automatically queued and will arrive when protection ends.<br/>
                        @endif
                        This is your only chance to build or train elites, spies, and wizards to coincide with leaving protection.
                        <br/>Don't forget to cast spells again.
                        <br/>Next, you'll perform each remaining tick individually.
                    </div>
                </div>
            @endif
            @if ($selectedDominion->protection_ticks_remaining == 9)
                @php
                    $landCalculator = app(\OpenDominion\Calculators\Dominion\LandCalculator::class);
                    $militaryCalculator = app(\OpenDominion\Calculators\Dominion\MilitaryCalculator::class);
                    $landSize = $landCalculator->getTotalLandIncoming($selectedDominion);
                    $minimumDefense = $militaryCalculator->getMinimumDefense(null, $landSize);
                @endphp
                <div class="row">
                    <div class="col-md-12 text-center">
                        This is your last chance to train specialists to coincide with leaving protection.
                        <br/>You'll need at least {{ number_format($minimumDefense) }} defense to attack or leave protection at this land size.
                    </div>
                </div>
            @endif
            @if ($selectedDominion->protection_ticks_remaining == 0 && !$selectedDominion->protection_finished)
                <div class="row">
                    <div class="col-md-12 text-center">
                        This is your last chance to go back and change anything before leaving protection.
                        <br/>Don't forget to select a hero.
                    </div>
                </div>
            @endif
        @endif
    </div>
@endif
