@php
    $battle = $view->model;
    $queueAhead = $view->canQueueAhead();
    $manual = array_filter($commandable, fn ($combatant) => !$combatant->automated && $view->canAct($combatant));
    $queuedTurns = $queueAhead && $manual ? min(array_map(fn ($combatant) => count($combatant->queue), $manual)) : 0;
    $orderTurn = $battle->current_turn + $queuedTurns;
    $formId = "battle-orders-{$battle->id}";
@endphp
<div class="battle-orders-box" data-battle-orders>
    <form id="{{ $formId }}" action="{{ route('dominion.heroes.battles.orders') }}" method="post">
        @csrf
        <input type="hidden" name="battle" value="{{ $battle->id }}">
    </form>

    <div class="battle-orders-top">
        <span class="battle-orders-title">Turn {{ $orderTurn }} orders</span>
        @if (count($commandable) > 1)
            <span class="battle-orders-count" data-orders-count data-total="{{ count($manual) }}">0 of {{ count($manual) }} chosen</span>
        @endif
        <button type="submit" form="{{ $formId }}" class="btn battle-orders-lock" data-orders-lock disabled>
            {{ $queueAhead ? "Queue turn {$orderTurn}" : "Lock in turn {$orderTurn}" }}
        </button>
    </div>

    <div class="battle-orders-heroes">
        @foreach ($commandable as $combatant)
            @php
                $model = $view->model($combatant);
                $borrowed = $view->isBorrowed($combatant);
                $ally = $view->isAlly($combatant);
                $canAct = $view->canAct($combatant);
                $onAutopilot = $combatant->automated || !$canAct;
            @endphp
            <div class="battle-hero-orders" data-hero-orders @if (!$onAutopilot) data-manual @endif>
                <div class="battle-hero-orders-head">
                    <span class="battle-card-medal"><i class="ra {{ $view->icon($combatant) }}"></i></span>
                    <span class="battle-card-id">
                        <span class="battle-card-name">{{ $combatant->name }} @if ($borrowed)<span class="battle-card-tag">Borrowed</span>@elseif ($ally)<span class="battle-card-tag">Ally</span>@endif</span>
                        @if ($queueAhead && $combatant->queue)
                            <span class="battle-hero-queued">
                                Queued:
                                @foreach ($view->queue($combatant) as $index => $queued)
                                    T{{ $queued['turn'] }} {{ $queued['ability'] }}@if ($queued['target']) → {{ $queued['target'] }}@endif
                                    <a href="{{ route('dominion.heroes.battles.action.delete', ['combatant' => $combatant->id, 'action' => $index]) }}" title="Remove" data-bs-toggle="tooltip"><i class="fa fa-times"></i></a>@if (!$loop->last) ·@endif
                                @endforeach
                            </span>
                        @endif
                    </span>
                    @if ($onAutopilot)
                        <span class="battle-pick battle-pick-auto">Autopilot</span>
                    @else
                        <span class="battle-pick" data-pick>Choose</span>
                    @endif
                </div>

                <div class="battle-hero-orders-body">
                    @if (!$canAct)
                        <div class="battle-autopilot-notice">{{ $combatant->name }} is out of time, so autopilot plays {{ $borrowed || $ally ? 'their' : 'your' }} turns.</div>
                    @elseif ($combatant->automated)
                        <form action="{{ route('dominion.heroes.battles') }}" method="post" class="battle-autopilot-notice">
                            @csrf
                            <input type="hidden" name="combatant" value="{{ $combatant->id }}">
                            <input type="hidden" name="strategy" value="{{ $combatant->ai }}">
                            <b>{{ $combatant->name }} is on autopilot ({{ $view->strategies()[$combatant->ai] ?? $combatant->ai }}).</b>
                            The AI picks {{ $borrowed || $ally ? 'their' : 'your' }} action when the turn resolves.
                            <div><button type="submit" class="btn btn-secondary btn-sm mt-2">Cancel autopilot</button></div>
                        </form>
                    @else
                        <input type="hidden" form="{{ $formId }}" name="orders[{{ $combatant->id }}][action]" value="" data-order-action>
                        <input type="hidden" form="{{ $formId }}" name="orders[{{ $combatant->id }}][target]" value="" data-order-target>
                        <div class="battle-actions">
                            @foreach ($view->abilities($combatant) as $ability)
                                @php
                                    $options = $ability['usable'] ? $view->targetOptions($combatant, $ability['key']) : [];
                                    $defaultTarget = $view->defaultTarget($combatant, $ability['key']);
                                    $status = $ability['cooldown'] > 0 ? 'Ready T' . ($battle->current_turn + $ability['cooldown']) : 'Ready';
                                    if ($ability['charges'] !== null) {
                                        $status = "{$ability['charges']} left";
                                    }
                                    $status = $ability['warning'] ?? $status;
                                @endphp
                                <button type="button" class="battle-action" title="{{ implode('<br>', array_map('e', [$ability['description'], ...$ability['rulesNotes']])) }}" data-bs-toggle="tooltip" data-bs-trigger="hover"
                                    data-action="{{ $ability['key'] }}" data-target="{{ $defaultTarget }}"
                                    data-label="{{ $ability['name'] }}{{ $defaultTarget !== null ? ' → ' . $view->battle->combatant($defaultTarget)->name : '' }}"
                                    @if ($options) data-picks @endif
                                    @disabled(!$ability['usable'])>
                                    <i class="ra {{ $view->abilityIcon($ability['key']) }}"></i>
                                    <span>{{ $ability['name'] }}</span>
                                    <small>{{ $ability['usable'] || $ability['cooldown'] > 0 || $ability['charges'] !== null ? $status : 'Unavailable' }}</small>
                                </button>
                            @endforeach
                        </div>
                        @foreach ($view->abilities($combatant) as $ability)
                            @php
                                $options = $ability['usable'] ? $view->targetOptions($combatant, $ability['key']) : [];
                            @endphp
                            @if ($options)
                                <div class="battle-target-pick" data-target-pick="{{ $ability['key'] }}" hidden>
                                    <div>{{ $ability['name'] }} {{ $ability['hostile'] ? 'who' : 'whom' }}?</div>
                                    <div class="battle-target-options">
                                        @foreach ($options as $option)
                                            <button type="button" class="battle-target battle-target-{{ $option['side'] }}" data-target="{{ $option['id'] }}"
                                                data-label="{{ $option['label'] === 'Yourself' ? $ability['name'] : ($option['side'] === 'enemy' ? "{$ability['name']} → {$option['label']}" : $option['label']) }}">
                                                {{ $option['label'] }} <small>{{ $option['health'] }}</small>
                                            </button>
                                        @endforeach
                                        <button type="button" class="battle-target" data-target-cancel>Cancel</button>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endif

                    <details class="battle-autopilot">
                        <summary><i class="ra ra-cog"></i> Autopilot: {{ $view->strategies()[$combatant->ai] ?? $combatant->ai }} · used if time runs out</summary>
                        <form action="{{ route('dominion.heroes.battles') }}" method="post">
                            @csrf
                            <input type="hidden" name="combatant" value="{{ $combatant->id }}">
                            <select name="strategy" class="form-select form-select-sm mt-2">
                                @foreach ($view->strategies() as $key => $strategyName)
                                    <option value="{{ $key }}" @selected($combatant->ai == $key)>{{ $strategyName }}</option>
                                @endforeach
                            </select>
                            <div class="form-check mt-2">
                                <input type="checkbox" id="automated-{{ $combatant->id }}" name="automated" class="form-check-input" @checked($combatant->automated)>
                                <label for="automated-{{ $combatant->id }}" class="form-check-label">
                                    Automate all of {{ $borrowed || $ally ? $combatant->name . "'s" : 'my' }} turns
                                </label>
                            </div>
                            <button class="btn btn-secondary btn-sm mt-2">Update</button>
                        </form>
                    </details>
                </div>
            </div>
        @endforeach
    </div>
</div>
