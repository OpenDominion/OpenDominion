@if (isset($selectedDominion) && $selectedDominion->protection_ticks_remaining > 0)
    <div class="alert-info" style="padding: 10px 15px; font-size: 12px;">
        <div class="pull-left">
            <i class="icon ra ra-shield"></i> Under Protection - Hour <b>{{ 73 - $selectedDominion->protection_ticks_remaining }}</b>
        </div>
        <div class="pull-right">
            <a href="{{ route('dominion.misc.tick') }}" class="alert-link">
                Proceed to next hour &raquo;
            </a>
        </div>
        <div class="clearfix"></div>
    </div>
@endif
