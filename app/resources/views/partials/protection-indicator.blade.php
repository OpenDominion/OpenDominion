@if (isset($selectedDominion) && $protectionService->isUnderProtection($selectedDominion))
    <div class="alert-info text-center" style="padding: 10px 15px; font-size: 13px;">
        <div class="row">
            <div class="col-xs-2 text-left">
                @if ($selectedDominion->protection_ticks_remaining < 72)
                    <a href="{{ route('dominion.misc.undo-tick') }}" class="btn btn-xs btn-danger" style="margin-right: 20px;">
                        &laquo; Undo
                    </a>
                @endif
            </div>
            <div class="col-xs-8 text-center">
                <i class="icon ra ra-shield"></i> Under Protection -
                <span class="text-nowrap">
                    @if ($selectedDominion->protection_ticks_remaining > 0)
                        <b>Hour {{ 73 - $selectedDominion->protection_ticks_remaining }}</b>
                        ({{ $selectedDominion->protection_ticks_remaining }} ticks remaining)
                    @else
                        <b>{{ $protectionService->getUnderProtectionHoursLeft($selectedDominion) }}</b> hours remaining
                    @endif
                </span>
            </div>
            <div class="col-xs-2 text-right">
                @if ($selectedDominion->protection_ticks_remaining > 0)
                    <a href="{{ route('dominion.misc.tick') }}" class="btn btn-xs btn-primary">
                        Next &raquo;
                    </a>
                @endif
            </div>
        </div>
    </div>
@endif
