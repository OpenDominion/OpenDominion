@php
    if ($type == 'magic') {
        $label = 'Cast ' . format_string($opType);
        $name = 'spell';
    } else {
        $label = 'Perform ' . format_string($opType);
        $name = 'operation';
    }
@endphp

@if ($bounty == null)
    <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, $opType]) }}" data-toggle="tooltip" title="Request a {{ format_string($opType) }}">
        <i class="fa fa-star-o" style="margin-top: 4px;"></i>
    </a>
@else
    <form action="{{ route('dominion.' . $type) }}" method="post" role="form">
        @csrf
        <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
        <input type="hidden" name="{{ $name }}" value="{{ $opType }}">
        <button
            type="submit"
            class="btn btn-xs btn-primary"
            data-toggle="tooltip"
            title="{{ $label }}<br><small>requested by {{ $bounty->sourceDominion->name }}<br>{{ $bounty->updated_at->diffForHumans() }}</small>"
            {{ ($bounty->sourceDominion->id == $selectedDominion->id) ? 'disabled' : null }}
        >
            <i class="fa fa-star"></i>
        </button>
    </form>
@endif
