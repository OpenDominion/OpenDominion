@if (isset($selectedDominion) && $selectedDominion->protection_ticks_remaining > 0)
    <div class="alert-info text-center" style="padding: 10px 15px; font-size: 12px;">
        <div class="pull-left">
            @if ($selectedDominion->protection_ticks_remaining < 72)
                <a href="{{ route('dominion.misc.undo-tick') }}" class="btn btn-xs btn-danger" style="margin-right: 20px;">
                    &laquo; Undo
                </a>
            @endif
        </div>
        <i class="icon ra ra-shield"></i> Under Protection - <b>Hour {{ 73 - $selectedDominion->protection_ticks_remaining }}</b> ({{ $selectedDominion->protection_ticks_remaining }} ticks remaining)
        <div class="pull-right">
            <a href="{{ route('dominion.misc.tick') }}" class="btn btn-xs btn-primary">
                Next &raquo;
            </a>
        </div>
        <div class="clearfix"></div>
    </div>
@endif
