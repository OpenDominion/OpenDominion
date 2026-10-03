@php
    $stats = $view->stats($combatant);
    $effects = $view->effects($combatant);
    $combatantModel = $view->model($combatant);
    $shield = $view->shield($combatant);
@endphp
<div class="col-sm-6">
    <table class="table table-sm {{ $combatant->isAlive() ? null : 'text-muted' }}">
        <thead>
            <tr>
                <th colspan=2 class="text-center">
                    {{ $combatant->name }}
                    @if ($view->isViewer($combatant))
                        (you)
                    @endif
                    @if (!$combatant->isAlive())
                        <i class="ra ra-skull" title="Defeated" data-bs-toggle="tooltip"></i>
                    @endif
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stats as $stat => $data)
                <tr>
                    <td>
                        <span class="{{ in_array($stat, ['focus', 'counter', 'recover']) && $stat == $combatant->lastAction ? 'text-warning' : null }}" data-bs-toggle="tooltip" title="{{ $data['tooltip'] }}">
                            {{ $data['label'] }}
                        </span>
                    </td>
                    <td>
                        @if ($stat == 'health')
                            {{ $combatant->currentHealth }}
                            @if ($shield > 0)
                                + <span class="text-aqua">{{ $shield }}</span>
                            @endif
                            /
                        @endif
                        <span class="{{ $data['value'] > $data['base'] ? 'text-green' : ($data['value'] < $data['base'] ? 'text-red' : null) }}">
                            {{ $data['value'] }}
                        </span>
                    </td>
                </tr>
            @endforeach
            @if ($effects)
                <tr>
                    <td colspan=2>
                        @foreach ($effects as $effect)
                            @php
                                $badge = match ($effect['kind']) {
                                    'buff' => 'bg-success',
                                    'debuff' => 'bg-danger',
                                    'innate' => 'bg-secondary',
                                    default => 'bg-info',
                                };
                            @endphp
                            <span class="badge {{ $badge }} mb-1" data-bs-toggle="tooltip" title="{{ $effect['description'] }}">
                                {{ $effect['name'] }}@if ($effect['stacks'] > 1) x{{ $effect['stacks'] }}@endif
                                @if ($effect['turns'] !== null)
                                    ({{ $effect['turns'] }})
                                @endif
                            </span>
                        @endforeach
                    </td>
                </tr>
            @endif
            @if ($combatant->isHuman() && $combatantModel)
                <tr>
                    <td><span data-bs-toggle="tooltip" title="Time remaining to set manual actions">Time</span></td>
                    <td>{{ rfloor($combatantModel->timeLeft() / 3600) }}h, {{ rfloor($combatantModel->timeLeft() % 3600 / 60) }}m</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
