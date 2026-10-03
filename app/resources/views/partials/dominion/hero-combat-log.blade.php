<table class="table table-sm">
    <thead>
        <tr>
            <th>Combat Log</th>
        </tr>
    </thead>
    @foreach ($battle->actions->sortByDesc('turn')->groupBy('turn') as $turn => $actions)
        <tr><td>Turn {{ $turn }}</td></tr>
        <tr><td>
            @foreach ($actions->where('action', '!=', 'status') as $action)
                @if ($action->combatant)
                    {{ $action->combatant->name }} selected {{ $view->abilityName($action->action) }}.<br/>
                @endif
            @endforeach
            @foreach ($actions->where('description', '!=', '') as $action)
                {{ $action->description }}<br/>
            @endforeach
        </td></tr>
    @endforeach
</table>
