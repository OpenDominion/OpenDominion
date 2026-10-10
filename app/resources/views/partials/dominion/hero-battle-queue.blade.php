<div class="mb-3">
    <label class="form-label">
        Actions in queue
    </label>
    <table class="table-sm">
        @foreach ($view->queue() as $idx => $queued)
            <tr>
                <td>{{ $queued['turn'] }}</td>
                <td>
                    {{ $queued['ability'] }}
                    @if ($queued['target'])
                        <small class="text-muted">&rarr; {{ $queued['target'] }}</small>
                    @endif
                </td>
                <td>
                    <a href="{{ route('dominion.heroes.battles.action.delete', ['combatant'=>$view->viewer->id, 'action'=>$idx]) }}">
                        <i class="fa fa-trash text-danger"></i>
                    </a>
                </td>
            </tr>
        @endforeach
    </table>
</div>
