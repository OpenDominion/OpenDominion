@if ($bounties->has($opType))
    @if ($bounties->get($opType)->source_dominion_id == $selectedDominion->id)
        <a href="{{ route('dominion.bounty-board.delete', [$dominion->id, $opType]) }}" data-toggle="tooltip" title="Cancel Bounty">
            <i class="fa fa-star text-yellow" style="margin-right: 10px;"></i>
        </a>
    @else
        <a data-toggle="tooltip" title="Op Requested">
            <i class="fa fa-star text-yellow" style="margin-right: 10px;"></i>
        </a>
    @endif
@else
    <a href="{{ route('dominion.bounty-board.create', [$dominion->id, $opType]) }}" data-toggle="tooltip" title="Request a {{ format_string($opType) }}">
        <i class="fa fa-star-o" style="margin-right: 10px;"></i>
    </a>
@endif
