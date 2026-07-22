@php
    $actions = array_values($actions);
    $actionTypeLabels = [
        'train' => 'Train Military',
        'construct' => 'Construct Buildings',
        'explore' => 'Explore Land',
        'rezone' => 'Rezone Land',
        'spell' => 'Cast Spell',
        'release' => 'Release Draftees',
        'draft_rate' => 'Set Draft Rate',
        'daily_bonus' => 'Daily Bonus',
    ];
    $usesDailyAutomation = collect($actions)->contains(function (array $action) {
        return $action['action'] !== 'daily_bonus';
    });
@endphp

<article class="automation-tick-card" aria-labelledby="tick-title-{{ $tick }}">
    <header class="automation-tick-head">
        <div class="automation-tick-title">
            <strong id="tick-title-{{ $tick }}">Day {{ $day }}, Hour {{ $hour }}</strong>
            <span>
                {{ count($actions) }} ordered {{ count($actions) === 1 ? 'action' : 'actions' }}
                · {{ $usesDailyAutomation ? 'uses 1 daily automation' : 'bonus only' }}
            </span>
        </div>
        <div class="automation-tick-tools">
            <button class="btn btn-sm automation-copy-tick" type="button" title="Copy complete tick"
                aria-label="Copy every action in tick +{{ $hours }}"
                data-bs-toggle="modal" data-bs-target="#copyTickModal-{{ $tick }}"
                {{ $isLocked ? 'disabled' : null }}>
                <i class="fa fa-copy" aria-hidden="true"></i>
                <span>Copy tick</span>
            </button>
            <button class="btn btn-sm automation-clear-tick" type="button" title="Clear all actions"
                aria-label="Clear all actions in tick +{{ $hours }}"
                data-bs-toggle="modal" data-bs-target="#clearTickModal"
                data-tick="{{ $tick }}" data-label="Day {{ $day }}, Hour {{ $hour }} (+{{ $hours }})"
                {{ $isLocked ? 'disabled' : null }}>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    <div class="automation-action-list" role="list" data-action-list="{{ $tick }}">
        @foreach ($actions as $index => $item)
            <div class="action-display-row" id="display-{{ $tick }}-{{ $index }}" role="listitem"
                data-action-row data-tick="{{ $tick }}" data-action-index="{{ $index }}">
                <span class="action-order-number" aria-hidden="true">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <button class="action-drag-handle" type="button"
                    data-drag-handle aria-label="Reorder action {{ $index + 1 }}"
                    aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
                    title="Drag to reorder · Alt + ↑/↓ for keyboard"
                    {{ count($actions) < 2 || $isLocked ? 'disabled' : null }}>
                    <i class="fa fa-grip-vertical" aria-hidden="true"></i>
                </button>
                <div class="automation-action-copy">
                    <strong>@include('pages.dominion.automation._action-label', ['item' => $item])</strong>
                    <span>{{ $actionTypeLabels[$item['action']] ?? 'Automated Action' }}</span>
                </div>
                <div class="automation-action-tools">
                    <button class="automation-action-tool" type="button" title="Edit"
                        aria-label="Edit action {{ $index + 1 }}"
                        onclick="toggleEditRow({{ $tick }}, {{ $index }})" {{ $isLocked ? 'disabled' : null }}>
                        <i class="fa fa-pencil" aria-hidden="true"></i>
                    </button>
                    <button class="automation-action-tool" type="button" title="Duplicate"
                        aria-label="Duplicate action {{ $index + 1 }}"
                        onclick="toggleDuplicateRow({{ $tick }}, {{ $index }})" {{ $isLocked ? 'disabled' : null }}>
                        <i class="fa fa-copy" aria-hidden="true"></i>
                    </button>
                    <form action="{{ route('dominion.bonuses.actions.delete') }}" method="post" class="d-inline">
                        @csrf
                        <input type="hidden" name="tick" value="{{ $tick }}" />
                        <input type="hidden" name="key" value="{{ $index }}" />
                        <button class="automation-action-tool is-danger" type="submit" title="Delete"
                            aria-label="Delete action {{ $index + 1 }}" {{ $isLocked ? 'disabled' : null }}>
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
                <form id="reorder-{{ $tick }}-{{ $index }}" action="{{ route('dominion.bonuses.actions.reorder') }}" method="post" class="d-none">
                    @csrf
                    <input type="hidden" name="tick" value="{{ $tick }}" />
                    <input type="hidden" name="key" value="{{ $index }}" />
                    <input type="hidden" name="target_key" value="{{ $index }}" data-reorder-target />
                </form>
            </div>

            <div class="automation-inline-form action-edit-row" id="edit-{{ $tick }}-{{ $index }}" style="display: none;">
                <form action="{{ route('dominion.bonuses.actions.edit') }}" method="post">
                    @csrf
                    <input type="hidden" name="tick" value="{{ $tick }}" />
                    <input type="hidden" name="edit_key" value="{{ $index }}" />
                    @include('partials.dominion.automation.action-form', [
                        'formId' => "edit-form-{$tick}-{$index}",
                        'item' => $item,
                        'showTick' => false,
                    ])
                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-sm btn-dark" onclick="toggleEditRow({{ $tick }}, {{ $index }})">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" {{ $isLocked ? 'disabled' : null }}>Save</button>
                    </div>
                </form>
            </div>

            <div class="automation-inline-form action-duplicate-row" id="duplicate-{{ $tick }}-{{ $index }}" style="display: none;">
                <form action="{{ route('dominion.bonuses.actions.duplicate') }}" method="post">
                    @csrf
                    <input type="hidden" name="source_tick" value="{{ $tick }}" />
                    <input type="hidden" name="source_key" value="{{ $index }}" />
                    <div class="mb-2">
                        <label class="form-label" for="duplicate-target-{{ $tick }}-{{ $index }}">Duplicate to tick</label>
                        <select class="form-select" id="duplicate-target-{{ $tick }}-{{ $index }}" name="target_tick">
                            @foreach (range(1, $maxScheduleHours) as $targetHours)
                                <option value="{{ $currentTick + $targetHours }}">
                                    Day {{ $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($targetHours)) }},
                                    Hour {{ $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($targetHours)) }}
                                    (+{{ $targetHours }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-sm btn-dark" onclick="toggleDuplicateRow({{ $tick }}, {{ $index }})">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" {{ $isLocked ? 'disabled' : null }}>Duplicate</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>

    <footer class="automation-tick-foot">
        <button class="btn btn-sm automation-add-action" type="button"
            onclick="toggleAddToTick({{ $tick }})" {{ $isLocked ? 'disabled' : null }}>
            <i class="fa fa-plus" aria-hidden="true"></i> Add action
        </button>
        <span>{{ count($actions) }} of {{ $maxActionsPerTick }}</span>
    </footer>
    <div class="automation-inline-form add-to-tick-form" id="add-to-tick-{{ $tick }}" style="display: none;">
        <form action="{{ route('dominion.bonuses.actions') }}" method="post">
            @csrf
            <input type="hidden" name="tick" value="{{ $tick }}" />
            @include('partials.dominion.automation.action-form', [
                'formId' => "add-form-{$tick}",
                'item' => null,
                'showTick' => false,
            ])
            <div class="mt-2 text-end">
                <button type="button" class="btn btn-sm btn-dark" onclick="toggleAddToTick({{ $tick }})">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary" {{ $isLocked ? 'disabled' : null }}>Save</button>
            </div>
        </form>
    </div>
</article>

<div class="modal fade" id="copyTickModal-{{ $tick }}" tabindex="-1"
    aria-labelledby="copyTickModalLabel-{{ $tick }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <form class="modal-content" action="{{ route('dominion.bonuses.actions.copy-tick') }}" method="post">
            @csrf
            <input type="hidden" name="source_tick" value="{{ $tick }}" />
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="copyTickModalLabel-{{ $tick }}">Copy complete tick +{{ $hours }}</h5>
                    <p class="mb-0 mt-1 text-body-secondary">Copy all {{ count($actions) }} ordered actions to one or more destinations.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <fieldset>
                    <legend class="fs-6 mb-2">Destination ticks</legend>
                    <div class="automation-choice-list">
                        @foreach (range(1, $maxScheduleHours) as $targetHours)
                            @php
                                $targetTick = $currentTick + $targetHours;
                                $targetActions = array_values($automationConfig[$targetTick] ?? []);
                                $wouldExceedLimit = count($targetActions) + count($actions) > $maxActionsPerTick;
                            @endphp
                            @if ($targetTick != $tick)
                                <label class="automation-choice {{ $wouldExceedLimit ? 'is-disabled' : null }}">
                                    <input class="form-check-input" type="checkbox" name="target_ticks[]"
                                        value="{{ $targetTick }}" {{ $wouldExceedLimit || $isLocked ? 'disabled' : null }}>
                                    <span>
                                        <strong>+{{ $targetHours }} · Day {{ $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($targetHours)) }}, Hour {{ $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($targetHours)) }}</strong>
                                        <small>
                                            @if ($wouldExceedLimit)
                                                Would exceed {{ $maxActionsPerTick }} actions.
                                            @elseif (count($targetActions))
                                                Append after {{ count($targetActions) }} existing {{ count($targetActions) === 1 ? 'action' : 'actions' }}.
                                            @else
                                                Create a new tick.
                                            @endif
                                        </small>
                                    </span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </fieldset>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : null }}>Copy tick</button>
            </div>
        </form>
    </div>
</div>
