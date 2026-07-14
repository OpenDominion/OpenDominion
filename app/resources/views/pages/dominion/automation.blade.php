@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-robot-arm"></i> Automated Actions</span>
                </div>
                <div class="card-body">
                    @php
                        $currentTick = $selectedDominion->round->getTick();
                        if ($selectedDominion->round->hasStarted()) {
                            $actionStartDate = now()->startOfHour();
                        } else {
                            $actionStartDate = $selectedDominion->round->start_date;
                        }
                        $isLocked = $selectedDominion->isLocked();
                    @endphp

                    <div class="mb-3">
                        <strong>Current Tick:</strong>
                        Day {{ $selectedDominion->round->daysInRound() }}, Hour {{ $selectedDominion->round->hoursInDay() }}
                    </div>

                    @if (!$selectedDominion->ai_enabled || empty($selectedDominion->ai_config))
                        <p><i>No automated actions scheduled.</i></p>
                    @else
                        @foreach ($selectedDominion->ai_config as $tick => $actions)
                            @php
                                $hours = $tick - $currentTick;
                                $day = $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($hours));
                                $hour = $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($hours));
                                $actions = array_values($actions);
                            @endphp
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center py-2">
                                    <strong>Day {{ $day }}, Hour {{ $hour }} (+{{ $hours }})</strong>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" title="Copy complete tick"
                                            aria-label="Copy every action in tick +{{ $hours }}"
                                            data-bs-toggle="modal" data-bs-target="#copyTickModal-{{ $tick }}"
                                            {{ $isLocked ? 'disabled' : null }}>
                                            <i class="fa fa-copy"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" type="button" title="Clear all actions"
                                            data-bs-toggle="modal" data-bs-target="#clearTickModal"
                                            data-tick="{{ $tick }}" data-label="Day {{ $day }}, Hour {{ $hour }} (+{{ $hours }})"
                                            {{ $isLocked ? 'disabled' : null }}>
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <colgroup>
                                            <col width="58px">
                                            <col>
                                            <col width="145px">
                                        </colgroup>
                                        <tbody data-action-list="{{ $tick }}">
                                        @foreach ($actions as $index => $item)
                                            <tr class="action-display-row" id="display-{{ $tick }}-{{ $index }}"
                                                data-action-row data-tick="{{ $tick }}" data-action-index="{{ $index }}">
                                                <td class="text-center text-muted align-middle">
                                                    <span class="action-order-number">{{ $index + 1 }}.</span>
                                                    <button class="btn btn-sm action-drag-handle" type="button"
                                                        data-drag-handle aria-label="Reorder action {{ $index + 1 }}"
                                                        aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
                                                        title="Drag to reorder · Alt + ↑/↓ for keyboard"
                                                        {{ count($actions) < 2 || $isLocked ? 'disabled' : null }}>
                                                        <i class="fa fa-grip-vertical" aria-hidden="true"></i>
                                                    </button>
                                                    <form id="reorder-{{ $tick }}-{{ $index }}" action="{{ route('dominion.bonuses.actions.reorder') }}" method="post" class="d-none">
                                                        @csrf
                                                        <input type="hidden" name="tick" value="{{ $tick }}" />
                                                        <input type="hidden" name="key" value="{{ $index }}" />
                                                        <input type="hidden" name="target_key" value="{{ $index }}" data-reorder-target />
                                                    </form>
                                                </td>
                                                <td class="align-middle">
                                                    @include('pages.dominion.automation._action-label', ['item' => $item])
                                                </td>
                                                <td class="text-end align-middle">
                                                    {{-- Edit --}}
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" title="Edit" onclick="toggleEditRow({{ $tick }}, {{ $index }})" {{ $isLocked ? 'disabled' : null }}>
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                    {{-- Duplicate --}}
                                                    <button class="btn btn-outline-secondary btn-sm" type="button" title="Duplicate" onclick="toggleDuplicateRow({{ $tick }}, {{ $index }})" {{ $isLocked ? 'disabled' : null }}>
                                                        <i class="fa fa-copy"></i>
                                                    </button>
                                                    {{-- Delete --}}
                                                    <form action="{{ route('dominion.bonuses.actions.delete') }}" method="post" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="tick" value="{{ $tick }}" />
                                                        <input type="hidden" name="key" value="{{ $index }}" />
                                                        <button class="btn btn-outline-danger btn-sm" type="submit" title="Delete" {{ $isLocked ? 'disabled' : null }}>
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            {{-- Edit row (hidden by default) --}}
                                            <tr class="action-edit-row" id="edit-{{ $tick }}-{{ $index }}" style="display: none;">
                                                <td colspan="3">
                                                    <form action="{{ route('dominion.bonuses.actions.edit') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="tick" value="{{ $tick }}" />
                                                        <input type="hidden" name="edit_key" value="{{ $index }}" />
                                                        @include('pages.dominion.automation._action-form', [
                                                            'formId' => "edit-form-{$tick}-{$index}",
                                                            'item' => $item,
                                                            'showTick' => false,
                                                        ])
                                                        <div class="mt-2 text-end">
                                                            <button type="button" class="btn btn-sm btn-dark" onclick="toggleEditRow({{ $tick }}, {{ $index }})">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isLocked ? 'disabled' : null }}>Save</button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                            {{-- Duplicate row (hidden by default) --}}
                                            <tr class="action-duplicate-row" id="duplicate-{{ $tick }}-{{ $index }}" style="display: none;">
                                                <td colspan="3">
                                                    <form action="{{ route('dominion.bonuses.actions.duplicate') }}" method="post">
                                                        @csrf
                                                        <input type="hidden" name="source_tick" value="{{ $tick }}" />
                                                        <input type="hidden" name="source_key" value="{{ $index }}" />
                                                        <div class="mb-2">
                                                            Duplicate to tick:
                                                            <select class="form-select" name="target_tick">
                                                                @foreach (range(1, $maxScheduleHours) as $h)
                                                                    <option value="{{ $currentTick + $h }}">
                                                                        Day {{ $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($h)) }},
                                                                        Hour {{ $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($h)) }}
                                                                        (+{{ $h }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="text-end">
                                                            <button type="button" class="btn btn-sm btn-dark" onclick="toggleDuplicateRow({{ $tick }}, {{ $index }})">Cancel</button>
                                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isLocked ? 'disabled' : null }}>Duplicate</button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                    {{-- Add action to this tick --}}
                                    <div class="p-2">
                                        <button class="btn btn-sm btn-primary" type="button" onclick="toggleAddToTick({{ $tick }})" {{ $isLocked ? 'disabled' : null }}>
                                            <i class="fa fa-plus"></i> Add Action
                                        </button>
                                        <div class="add-to-tick-form mt-2" id="add-to-tick-{{ $tick }}" style="display: none;">
                                            <form action="{{ route('dominion.bonuses.actions') }}" method="post">
                                                @csrf
                                                <input type="hidden" name="tick" value="{{ $tick }}" />
                                                @include('pages.dominion.automation._action-form', [
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
                                    </div>
                                </div>
                            </div>

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
                                                            $targetActions = array_values($selectedDominion->ai_config[$targetTick] ?? []);
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
                        @endforeach
                    @endif

                    {{-- Schedule New Tick --}}
                    <div class="mt-3">
                        <button class="btn btn-primary" type="button" onclick="toggleNewTick()" {{ $isLocked ? 'disabled' : null }}>
                            <i class="fa fa-plus"></i> Schedule New Tick
                        </button>
                        <div id="new-tick-form" class="mt-2" style="display: none;">
                            <form action="{{ route('dominion.bonuses.actions') }}" method="post">
                                @csrf
                                <div class="mb-2">
                                    Tick:
                                    <select class="form-select" name="tick" {{ $isLocked ? 'disabled' : null }}>
                                        @foreach (range(1, $maxScheduleHours) as $hours)
                                            <option value="{{ $currentTick + $hours }}" {{ (($currentTick + $hours) == old('tick')) ? 'selected' : null }}>
                                                Day {{ $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($hours)) }},
                                                Hour {{ $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($hours)) }}
                                                (+{{ $hours }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @include('pages.dominion.automation._action-form', [
                                    'formId' => 'new-tick-action-form',
                                    'item' => null,
                                    'showTick' => false,
                                ])
                                <div class="mt-2 text-end">
                                    <button type="button" class="btn btn-dark" onclick="toggleNewTick()">Cancel</button>
                                    <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : null }}>Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card mb-3 automation-templates-card">
                <div class="card-header">
                    <span class="card-title">Saved Templates</span>
                    <span class="badge text-bg-secondary float-end">{{ collect($automationTemplates)->filter()->count() }}/{{ $maxTemplateSlots }}</span>
                </div>
                <div class="card-body p-2">
                    @foreach ($automationTemplates as $slot => $template)
                        @php
                            $templateTickCount = $template ? count($template['ticks']) : 0;
                            $templateActionCount = $template ? collect($template['ticks'])->sum(function ($templateTick) {
                                return count($templateTick['actions']);
                            }) : 0;
                        @endphp
                        <article class="automation-template {{ $template ? null : 'is-empty' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <small class="text-body-secondary">Slot {{ $slot + 1 }}</small>
                                    <h3 class="h6 mb-1">{{ $template['name'] ?? 'Empty template' }}</h3>
                                </div>
                                @if ($template)
                                    <form action="{{ route('dominion.bonuses.actions.templates') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="operation" value="delete" />
                                        <input type="hidden" name="slot" value="{{ $slot }}" />
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete template"
                                            aria-label="Delete template {{ $template['name'] }}" {{ $isLocked ? 'disabled' : null }}>
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                            @if ($template)
                                <p class="mb-2 text-body-secondary">{{ $templateTickCount }} {{ $templateTickCount === 1 ? 'tick' : 'ticks' }} · {{ $templateActionCount }} {{ $templateActionCount === 1 ? 'action' : 'actions' }}</p>
                                <div class="template-offsets mb-2" aria-label="Relative tick offsets">
                                    @foreach ($template['ticks'] as $templateTick)
                                        <span>+{{ $templateTick['offset'] }}</span>
                                    @endforeach
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-primary flex-grow-1" type="button"
                                        data-bs-toggle="modal" data-bs-target="#loadTemplateModal-{{ $slot }}"
                                        {{ $isLocked ? 'disabled' : null }}>Load</button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" title="Replace template"
                                        aria-label="Replace template {{ $template['name'] }}"
                                        data-bs-toggle="modal" data-bs-target="#saveTemplateModal-{{ $slot }}"
                                        {{ $isLocked ? 'disabled' : null }}>
                                        <i class="fa fa-rotate"></i>
                                    </button>
                                </div>
                            @else
                                <p class="mb-2 text-body-secondary">Save the current relative schedule here.</p>
                                <button class="btn btn-sm btn-outline-primary w-100" type="button"
                                    data-bs-toggle="modal" data-bs-target="#saveTemplateModal-{{ $slot }}"
                                    {{ $isLocked || empty($selectedDominion->ai_config) ? 'disabled' : null }}>
                                    <i class="fa fa-plus me-1"></i> Save here
                                </button>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    <p>You can schedule {{ $allowedActions }} automations per day, which reset with your daily bonuses.</p>
                    <p>Each tick that you automate can consist of up to {{ $maxActionsPerTick }} actions in sequence.</p>
                    <p>Actions cannot be scheduled more than {{ $maxScheduleHours }} hours in advance and are performed ~30 minutes into the hour.</p>
                    <p>In the event that you do not have enough resources to perform an action, it will instead use the max that you can afford.</p>
                    <p>Taking your daily land and platinum bonuses will not count toward your daily automation limit.</p>
                    <p>You have <b>{{ $selectedDominion->daily_actions }}</b> automation(s) remaining today.</p>
                </div>
            </div>
        </div>

    </div>

    @foreach ($automationTemplates as $slot => $template)
        <div class="modal fade" id="saveTemplateModal-{{ $slot }}" tabindex="-1"
            aria-labelledby="saveTemplateModalLabel-{{ $slot }}" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" action="{{ route('dominion.bonuses.actions.templates') }}" method="post">
                    @csrf
                    <input type="hidden" name="operation" value="save" />
                    <input type="hidden" name="slot" value="{{ $slot }}" />
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="saveTemplateModalLabel-{{ $slot }}">
                                {{ $template ? 'Replace' : 'Save' }} template slot {{ $slot + 1 }}
                            </h5>
                            <p class="mb-0 mt-1 text-body-secondary">Snapshot the current actions at their relative +1 to +12 offsets.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="template-name-{{ $slot }}">Template name</label>
                        <input class="form-control" id="template-name-{{ $slot }}" name="name" maxlength="32"
                            value="{{ $template['name'] ?? '' }}" placeholder="e.g. Growth opening" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : null }}>Save template</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($template)
            <div class="modal fade" id="loadTemplateModal-{{ $slot }}" tabindex="-1"
                aria-labelledby="loadTemplateModalLabel-{{ $slot }}" aria-hidden="true">
                <div class="modal-dialog">
                    <form class="modal-content" action="{{ route('dominion.bonuses.actions.templates') }}" method="post">
                        @csrf
                        <input type="hidden" name="operation" value="load" />
                        <input type="hidden" name="slot" value="{{ $slot }}" />
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="loadTemplateModalLabel-{{ $slot }}">Load “{{ $template['name'] }}”</h5>
                                <p class="mb-0 mt-1 text-body-secondary">Offsets are applied from the current tick, not the time when this template was saved.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="template-load-preview mb-3">
                                @foreach ($template['ticks'] as $templateTick)
                                    <span><strong>+{{ $templateTick['offset'] }}</strong> · {{ count($templateTick['actions']) }} {{ count($templateTick['actions']) === 1 ? 'action' : 'actions' }}</span>
                                @endforeach
                            </div>
                            <fieldset>
                                <legend class="fs-6 mb-2">Load behavior</legend>
                                <div class="automation-choice-list">
                                    <label class="automation-choice">
                                        <input class="form-check-input" type="radio" name="mode" value="replace" checked>
                                        <span>
                                            <strong>Replace current schedule</strong>
                                            <small>Load this template exactly as saved.</small>
                                        </span>
                                    </label>
                                    <label class="automation-choice">
                                        <input class="form-check-input" type="radio" name="mode" value="open">
                                        <span>
                                            <strong>Fill open ticks only</strong>
                                            <small>Keep existing ticks and skip collisions.</small>
                                        </span>
                                    </label>
                                </div>
                            </fieldset>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" {{ $isLocked ? 'disabled' : null }}>Load template</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    <div class="modal fade" id="quickFillManagerModal" tabindex="-1"
        aria-labelledby="quickFillManagerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="quickFillManagerModalLabel">My quick fills</h5>
                        <p class="mb-0 mt-1 text-body-secondary">Keep up to five deliberate shortcuts. Typed history is never saved automatically.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="quickFillManagerBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="quickFillManagerSave">Save quick fills</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Clear Tick Confirmation Modal --}}
    <div class="modal fade" id="clearTickModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clear Tick</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to clear all actions for <strong id="clearTickLabel"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancel</button>
                    <form id="clearTickForm" action="{{ route('dominion.bonuses.actions.clear') }}" method="post">
                        @csrf
                        <input type="hidden" name="tick" id="clearTickValue" />
                        <button type="submit" class="btn btn-danger">Clear All</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('inline-styles')
    <style>
        .automation-template {
            padding: .75rem;
            border: 1px solid var(--od-border-accent, var(--bs-border-color));
            background: var(--od-surface, var(--bs-body-bg));
        }
        .automation-template + .automation-template { margin-top: .5rem; }
        .automation-template.is-empty { border-style: dashed; }
        .template-offsets { display: flex; flex-wrap: wrap; gap: .3rem; }
        .template-offsets span {
            padding: .15rem .4rem;
            color: var(--od-primary, var(--bs-primary));
            background: color-mix(in srgb, var(--od-primary, var(--bs-primary)) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--od-primary, var(--bs-primary)) 30%, transparent);
            font-size: .75rem;
            font-weight: 700;
        }
        .template-load-preview { display: flex; flex-wrap: wrap; gap: .4rem; }
        .template-load-preview span {
            padding: .4rem .55rem;
            border: 1px solid var(--od-border-accent, var(--bs-border-color));
            background: var(--od-surface, var(--bs-tertiary-bg));
        }
        .automation-choice-list { display: grid; gap: .5rem; }
        .automation-choice {
            min-height: 64px;
            padding: .7rem .8rem;
            display: grid;
            grid-template-columns: 24px minmax(0, 1fr);
            column-gap: 15px;
            align-items: start;
            border: 1px solid var(--od-border-accent, var(--bs-border-color));
            background: var(--od-surface, var(--bs-body-bg));
            cursor: pointer;
        }
        .automation-choice:has(input:checked) {
            border-color: var(--od-primary, var(--bs-primary));
            background: var(--od-primary-bg-soft, color-mix(in srgb, var(--od-primary, var(--bs-primary)) 12%, var(--od-surface, var(--bs-body-bg))));
        }
        .automation-choice.is-disabled { opacity: .55; cursor: not-allowed; }
        .automation-choice input { width: 20px; height: 20px; margin: .1rem 0 0; }
        .automation-choice span { min-width: 0; display: grid; gap: .25rem; }
        .automation-choice strong, .automation-choice small { display: block; line-height: 1.35; }
        .automation-choice small { color: var(--od-text-secondary, var(--bs-secondary-color)); }

        .action-drag-handle {
            width: 30px;
            padding-inline: 0;
            color: var(--od-text-secondary, var(--bs-secondary-color));
            cursor: grab;
            touch-action: none;
        }
        .action-drag-handle:active { cursor: grabbing; }
        .action-display-row.is-dragging { opacity: .45; }
        .action-display-row.drop-before { box-shadow: inset 0 3px 0 var(--od-primary, var(--bs-primary)); }
        .action-display-row.drop-after { box-shadow: inset 0 -3px 0 var(--od-primary, var(--bs-primary)); }
        .action-order-number { display: inline-block; min-width: 1.25rem; }

        .automation-quick-fill {
            --quick-fill-surface: var(--od-surface, var(--bs-secondary-bg));
            --quick-fill-text: var(--od-text-body, var(--bs-body-color));
            --quick-fill-muted: var(--od-text-secondary, var(--bs-secondary-color));
            --quick-fill-border: var(--od-border-accent, var(--bs-border-color));
            --quick-fill-accent: var(--od-primary, var(--bs-primary));
            --quick-fill-active: var(--od-primary-bg-soft, color-mix(in srgb, var(--quick-fill-accent) 14%, var(--quick-fill-surface)));
            padding: .75rem;
            border: 1px solid var(--quick-fill-border);
            background: var(--quick-fill-surface);
        }
        .quick-fill-manage { color: var(--quick-fill-muted); text-decoration: none; }
        .quick-fill-combobox { position: relative; }
        .quick-fill-combobox input[aria-expanded="true"] {
            border-color: var(--quick-fill-accent);
            box-shadow: inset 3px 0 0 var(--quick-fill-accent);
        }
        .quick-fill-popover {
            position: absolute;
            inset-inline: 0;
            top: 100%;
            z-index: 1055;
            margin-top: -1px;
            color: var(--quick-fill-text);
            background: var(--quick-fill-surface);
            border: 1px solid var(--quick-fill-border);
            box-shadow: 0 .75rem 1.75rem rgba(0, 0, 0, .18);
        }
        .quick-fill-popover[hidden] { display: none; }
        .quick-fill-listbox { max-height: min(280px, 42vh); margin: 0; padding: .25rem; overflow-y: auto; list-style: none; }
        .quick-fill-option {
            min-height: 46px;
            padding: .5rem .65rem;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: .75rem;
            border-left: 3px solid transparent;
            cursor: pointer;
        }
        .quick-fill-option + .quick-fill-option { border-top: 1px solid color-mix(in srgb, var(--quick-fill-border) 55%, transparent); }
        .quick-fill-option[aria-selected="true"], .quick-fill-option:hover {
            background: var(--quick-fill-active);
            border-left-color: var(--quick-fill-accent);
        }
        .quick-fill-option strong { min-width: 0; font-size: .95rem; line-height: 1.25; }
        .quick-fill-option span { color: var(--quick-fill-muted); font-size: .7rem; font-weight: 600; text-align: right; }
        .quick-fill-foot { padding: .4rem .65rem; color: var(--quick-fill-muted); border-top: 1px solid var(--quick-fill-border); font-size: .75rem; }
        .quick-fill-status { min-height: 1.25rem; margin-top: .25rem; color: var(--quick-fill-muted); font-size: .85rem; }
        .quick-fill-status.is-matched { color: var(--od-success, var(--bs-success)); }
        .quick-fill-status.is-matched::before { content: "✓ "; font-weight: 700; }
        .quick-fill-manager-summary { padding: .65rem .75rem; border-left: 3px solid var(--od-primary, var(--bs-primary)); background: var(--od-surface, var(--bs-tertiary-bg)); }
        .quick-fill-manager-list { display: grid; gap: .5rem; }
        .quick-fill-manager-row { padding: .65rem; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .4rem .6rem; border: 1px solid var(--od-border-accent, var(--bs-border-color)); }
        .quick-fill-manager-tools { display: flex; gap: .25rem; }
        .quick-fill-manager-state { grid-column: 1 / -1; min-height: 1.1rem; color: var(--od-text-secondary, var(--bs-secondary-color)); font-size: .8rem; }
        .quick-fill-manager-state.is-valid { color: var(--od-success, var(--bs-success)); }
        .quick-fill-manager-state.is-invalid { color: var(--od-danger, var(--bs-danger)); }

        @media (max-width: 575.98px) {
            .action-display-row { display: grid; grid-template-columns: 58px minmax(0, 1fr); }
            .action-order-number { display: none; }
            .action-drag-handle { width: 44px; min-height: 44px; }
            .action-display-row > td:nth-child(3) { grid-column: 2; padding-top: 0; text-align: left !important; }
            .action-display-row > td:nth-child(3) .btn { min-width: 38px; min-height: 38px; }
            .quick-fill-option { min-height: 48px; }
            .quick-fill-manager-row { grid-template-columns: 1fr; }
            .quick-fill-manager-tools .btn { min-width: 44px; min-height: 44px; flex: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .automation-template, .quick-fill-popover, .action-display-row { transition: none !important; }
        }
    </style>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var quickFillOptions = @json($quickFillOptions);
            var maxQuickFills = 5;
            var quickFillStorageKey = quickFillOptions.storageKey;
            var savedQuickFills = loadQuickFills();
            var quickFillDraft = [];
            var dragState = null;

            function escapeHtml(value) {
                return String(value).replace(/[&<>'"]/g, function (character) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[character];
                });
            }

            function loadQuickFills() {
                var defaults = ['build 45 alchemy', 'draft 65', 'db plat'];
                try {
                    var stored = JSON.parse(localStorage.getItem(quickFillStorageKey));
                    if (Array.isArray(stored)) {
                        return stored.filter(function (value) { return typeof value === 'string'; }).slice(0, maxQuickFills);
                    }
                } catch (error) {}
                return defaults;
            }

            function persistQuickFills() {
                try {
                    localStorage.setItem(quickFillStorageKey, JSON.stringify(savedQuickFills));
                } catch (error) {}
            }

            function normalizedWords(value) {
                return value.toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim().split(' ').filter(Boolean);
            }

            function findMention(options, text) {
                var normalized = normalizedWords(text).join(' ');
                var exact = options.find(function (option) {
                    return normalized.includes(normalizedWords(option.label).join(' '));
                });
                if (exact) return exact.key;

                var ignoredWords = new Set([
                    'bonus', 'build', 'cast', 'construct', 'construction', 'daily', 'db', 'draft', 'exploration',
                    'explore', 'recruit', 'release', 'rezone', 'rezoning', 'set', 'spell', 'to', 'train', 'training', 'zone'
                ]);
                var inputTokens = normalizedWords(text).filter(function (token) {
                    return token.length >= 2 && !ignoredWords.has(token) && !/^\d+$/.test(token);
                });
                var matches = options.filter(function (option) {
                    var labelTokens = normalizedWords(option.label);
                    return inputTokens.some(function (inputToken) {
                        return labelTokens.some(function (labelToken) { return labelToken.startsWith(inputToken); });
                    });
                });
                return matches.length === 1 ? matches[0].key : null;
            }

            function parseQuickFill(value) {
                var text = value.trim().toLowerCase();
                if (!text) return null;
                var action = null;
                if (/\b(rezone|rezoning|zone)\b/.test(text)) action = 'rezone';
                else if (/\b(construct|construction|build)\b/.test(text)) action = 'construct';
                else if (/\b(train|training|recruit)\b/.test(text)) action = 'train';
                else if (/\b(explore|exploration)\b/.test(text)) action = 'explore';
                else if (/\b(cast|spell)\b/.test(text)) action = 'spell';
                else if (/\brelease\b/.test(text)) action = 'release';
                else if (/\bdraft\b/.test(text)) action = 'draft_rate';
                else if (/\b(db|bonus|daily|plat|platinum)\b/.test(text)) action = 'daily_bonus';
                if (!action) return null;

                var amountMatch = text.match(/\b(\d{1,5})\b/);
                var parsed = {action: action, key: null, key2: null, amount: amountMatch ? Number(amountMatch[1]) : null};
                if (action === 'rezone') {
                    var zones = text.split(/\s+to\s+/, 2);
                    parsed.key = findMention(quickFillOptions.landTypes, zones[0]);
                    parsed.key2 = findMention(quickFillOptions.landTypes, zones[1] || '');
                } else if (action === 'train') parsed.key = findMention(quickFillOptions.units, text);
                else if (action === 'construct') parsed.key = findMention(quickFillOptions.buildings, text);
                else if (action === 'explore') parsed.key = findMention(quickFillOptions.landTypes, text);
                else if (action === 'spell') parsed.key = findMention(quickFillOptions.spells, text);
                else if (action === 'daily_bonus') parsed.key = findMention(quickFillOptions.bonuses, text);
                return parsed;
            }

            function missingQuickFillFields(parsed) {
                var missing = [];
                if (['train', 'construct', 'explore', 'spell', 'daily_bonus', 'rezone'].includes(parsed.action) && !parsed.key) missing.push('selection');
                if (parsed.action === 'rezone' && !parsed.key2) missing.push('target land type');
                if (!['spell', 'daily_bonus'].includes(parsed.action) && parsed.amount === null) missing.push('amount');
                return missing;
            }

            function optionLabel(options, key) {
                var option = options.find(function (item) { return item.key === key; });
                return option ? option.label : '';
            }

            function quickFillSummary(parsed) {
                if (parsed.action === 'train') return 'Train ' + parsed.amount + ' ' + optionLabel(quickFillOptions.units, parsed.key);
                if (parsed.action === 'construct') return 'Construct ' + parsed.amount + ' ' + optionLabel(quickFillOptions.buildings, parsed.key);
                if (parsed.action === 'explore') return 'Explore ' + parsed.amount + ' ' + optionLabel(quickFillOptions.landTypes, parsed.key);
                if (parsed.action === 'rezone') return 'Rezone ' + parsed.amount + ' ' + optionLabel(quickFillOptions.landTypes, parsed.key) + ' to ' + optionLabel(quickFillOptions.landTypes, parsed.key2);
                if (parsed.action === 'spell') return 'Cast ' + optionLabel(quickFillOptions.spells, parsed.key);
                if (parsed.action === 'release') return 'Release ' + parsed.amount + ' draftees';
                if (parsed.action === 'draft_rate') return 'Set draft rate ' + parsed.amount + '%';
                return 'Daily bonus ' + optionLabel(quickFillOptions.bonuses, parsed.key);
            }

            function targetWords(value) {
                var ignored = new Set([
                    'bonus', 'build', 'cast', 'construct', 'construction', 'daily', 'db', 'draft', 'exploration',
                    'explore', 'recruit', 'release', 'rezone', 'rezoning', 'set', 'spell', 'to', 'train', 'training', 'zone'
                ]);
                return normalizedWords(value).filter(function (word) { return !ignored.has(word); });
            }

            function matchesTarget(phrase, value) {
                var queryWords = targetWords(value);
                if (!queryWords.length) return true;
                var phraseWords = normalizedWords(phrase);
                return queryWords.every(function (queryWord) {
                    return phraseWords.some(function (phraseWord) { return phraseWord.startsWith(queryWord); });
                });
            }

            function contextualSuggestions(value) {
                var parsed = parseQuickFill(value);
                if (!parsed) return [];
                var amount = parsed.amount === null ? '' : parsed.amount + ' ';
                var phrases = [];
                var source = 'Current game option';
                if (parsed.action === 'train') {
                    phrases = quickFillOptions.units.map(function (option) { return 'train ' + amount + option.label.toLowerCase(); });
                    source = 'Current ' + quickFillOptions.race + ' unit';
                } else if (parsed.action === 'construct') {
                    phrases = quickFillOptions.buildings.map(function (option) { return 'build ' + amount + option.label.toLowerCase(); });
                } else if (parsed.action === 'explore') {
                    phrases = quickFillOptions.landTypes.map(function (option) { return 'explore ' + amount + option.label.toLowerCase(); });
                } else if (parsed.action === 'spell') {
                    phrases = quickFillOptions.spells.map(function (option) { return 'cast ' + option.label; });
                    source = 'Current ' + quickFillOptions.race + ' spell';
                } else if (parsed.action === 'daily_bonus') {
                    phrases = ['db land', 'db plat'];
                } else if (parsed.action === 'release' && parsed.amount !== null) {
                    phrases = ['release ' + parsed.amount];
                } else if (parsed.action === 'draft_rate' && parsed.amount !== null) {
                    phrases = ['draft ' + parsed.amount];
                }
                return phrases.filter(function (phrase) { return matchesTarget(phrase, value); }).map(function (phrase) {
                    return {phrase: phrase, source: source};
                });
            }

            function quickFillValidity(phrase) {
                var parsed = parseQuickFill(phrase);
                var valid = parsed && missingQuickFillFields(parsed).length === 0;
                if (valid && parsed.action === 'rezone' && parsed.key === parsed.key2) valid = false;
                return {valid: Boolean(valid), parsed: parsed};
            }

            function suggestionsFor(value) {
                var input = value.trim();
                var inputAction = parseQuickFill(input);
                var contextual = input ? contextualSuggestions(input) : [];
                var personal = savedQuickFills.flatMap(function (phrase) {
                    var validity = quickFillValidity(phrase);
                    if (!validity.valid) return [];
                    if (!input) return [{phrase: phrase, source: 'My quick fill'}];
                    if (inputAction && validity.parsed.action !== inputAction.action) return [];
                    return matchesTarget(phrase, input) ? [{phrase: phrase, source: 'My quick fill'}] : [];
                });
                var seen = new Set();
                return (input ? contextual.concat(personal) : personal).filter(function (suggestion) {
                    var key = suggestion.phrase.toLowerCase();
                    if (seen.has(key)) return false;
                    seen.add(key);
                    return true;
                }).slice(0, maxQuickFills);
            }

            function initActionForm(container) {
                if (container.data('automation-initialized')) return;
                container.data('automation-initialized', true);
                var actionSelect = container.find('select[name=action]');
                var optionContainers = container.find('.action-options');
                var root = container.find('[data-quick-fill-root]').get(0);

                function toggleDropdowns(value) {
                    optionContainers.hide();
                    optionContainers.children('select,input').prop('disabled', true);

                    var selected = container.find('.' + value);
                    selected.children('select,input').prop('disabled', false);
                    selected.show();
                }

                actionSelect.on('change', function (e) {
                    toggleDropdowns(e.currentTarget.value);
                });

                if (actionSelect.val()) {
                    toggleDropdowns(actionSelect.val());
                }

                if (!root) return;
                var input = root.querySelector('[role="combobox"]');
                var popover = root.querySelector('[data-quick-fill-popover]');
                var listbox = root.querySelector('[role="listbox"]');
                var foot = root.querySelector('[data-quick-fill-foot]');
                var status = root.querySelector('.quick-fill-status');
                var live = root.querySelector('[data-quick-fill-live]');
                var visibleSuggestions = [];
                var activeSuggestion = -1;

                function closeSuggestions() {
                    popover.hidden = true;
                    input.setAttribute('aria-expanded', 'false');
                    input.removeAttribute('aria-activedescendant');
                    activeSuggestion = -1;
                }

                function refreshSuggestions(open) {
                    visibleSuggestions = suggestionsFor(input.value);
                    activeSuggestion = -1;
                    input.removeAttribute('aria-activedescendant');
                    listbox.innerHTML = visibleSuggestions.map(function (suggestion, index) {
                        return '<li class="quick-fill-option" id="' + input.id + '-option-' + index + '" role="option" aria-selected="false" data-suggestion-index="' + index + '">' +
                            '<strong>' + escapeHtml(suggestion.phrase) + '</strong><span>' + escapeHtml(suggestion.source) + '</span></li>';
                    }).join('');
                    foot.textContent = visibleSuggestions.length + ' shown · ' + maxQuickFills + ' maximum · no automatic history';
                    live.textContent = visibleSuggestions.length ? visibleSuggestions.length + ' quick fill suggestions available.' : '';
                    popover.hidden = !(open && visibleSuggestions.length);
                    input.setAttribute('aria-expanded', String(!popover.hidden));
                }

                function setActiveSuggestion(index) {
                    if (!visibleSuggestions.length) return;
                    activeSuggestion = (index + visibleSuggestions.length) % visibleSuggestions.length;
                    var options = Array.from(listbox.querySelectorAll('[role="option"]'));
                    options.forEach(function (option, optionIndex) {
                        option.setAttribute('aria-selected', String(optionIndex === activeSuggestion));
                    });
                    input.setAttribute('aria-activedescendant', options[activeSuggestion].id);
                    options[activeSuggestion].scrollIntoView({block: 'nearest'});
                }

                function clearQuickFillValues() {
                    container.find('.action-options select[name=key], .action-options select[name=key2]').val('');
                    container.find('input[name=amount]').val('');
                }

                function applyQuickFill() {
                    if (input.value.trim()) {
                        clearQuickFillValues();
                    }

                    var parsed = parseQuickFill(input.value);
                    if (!parsed) {
                        status.textContent = input.value.trim() ? 'Keep typing or use the Action list below.' : '';
                        status.classList.remove('is-matched');
                        return;
                    }

                    actionSelect.val(parsed.action);
                    toggleDropdowns(parsed.action);
                    if (parsed.key) container.find('.action-options.' + parsed.action + ' select[name=key]').val(parsed.key);
                    if (parsed.key2) container.find('.action-options.rezone select[name=key2]').val(parsed.key2);
                    if (parsed.amount !== null) container.find('input[name=amount]').val(parsed.amount);
                    var missing = missingQuickFillFields(parsed);
                    status.textContent = missing.length
                        ? 'Matched ' + actionSelect.find('option:selected').text() + '. Select ' + missing.join(' and ') + ' below.'
                        : 'Matched ' + quickFillSummary(parsed) + '. Review the fields below.';
                    status.classList.add('is-matched');
                }

                function chooseSuggestion(index) {
                    if (!visibleSuggestions[index]) return;
                    input.value = visibleSuggestions[index].phrase;
                    applyQuickFill();
                    closeSuggestions();
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }

                input.addEventListener('input', function () {
                    applyQuickFill();
                    refreshSuggestions(true);
                });
                input.addEventListener('focus', function () { refreshSuggestions(true); });
                input.addEventListener('keydown', function (event) {
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        if (popover.hidden) refreshSuggestions(true);
                        setActiveSuggestion(activeSuggestion + (event.key === 'ArrowDown' ? 1 : -1));
                    } else if (event.key === 'Enter' && activeSuggestion >= 0) {
                        event.preventDefault();
                        chooseSuggestion(activeSuggestion);
                    } else if (event.key === 'Escape' && !popover.hidden) {
                        event.preventDefault();
                        event.stopPropagation();
                        closeSuggestions();
                    }
                });
                listbox.addEventListener('pointerdown', function (event) { event.preventDefault(); });
                listbox.addEventListener('click', function (event) {
                    var option = event.target.closest('[data-suggestion-index]');
                    if (option) chooseSuggestion(Number(option.dataset.suggestionIndex));
                });
                document.addEventListener('pointerdown', function (event) {
                    if (!root.contains(event.target)) closeSuggestions();
                });
                document.addEventListener('quickfillschange', function () {
                    refreshSuggestions(document.activeElement === input);
                });
                container.on('change', '.action-options select, .action-options input', function () {
                    if (!input.value.trim()) return;
                    status.textContent = 'Quick fill applied; structured fields edited manually.';
                    status.classList.remove('is-matched');
                });
            }

            $('.action-form-container:visible').each(function () {
                initActionForm($(this));
            });

            window.initActionForm = initActionForm;

            window.toggleEditRow = function (tick, index) {
                var display = $('#display-' + tick + '-' + index);
                var edit = $('#edit-' + tick + '-' + index);
                edit.toggle();
                display.toggle();
                if (edit.is(':visible')) {
                    initActionForm(edit.find('.action-form-container'));
                }
            };

            window.toggleDuplicateRow = function (tick, index) {
                $('#duplicate-' + tick + '-' + index).toggle();
            };

            window.toggleAddToTick = function (tick) {
                var form = $('#add-to-tick-' + tick);
                form.toggle();
                if (form.is(':visible')) {
                    initActionForm(form.find('.action-form-container'));
                }
            };

            window.toggleNewTick = function () {
                var form = $('#new-tick-form');
                form.toggle();
                if (form.is(':visible')) {
                    initActionForm(form.find('.action-form-container'));
                }
            };

            $('#clearTickModal').on('show.bs.modal', function (e) {
                var button = $(e.relatedTarget);
                $('#clearTickValue').val(button.data('tick'));
                $('#clearTickLabel').text(button.data('label'));
            });

            function updateQuickFillCounts() {
                document.querySelectorAll('.quick-fill-count').forEach(function (count) {
                    count.textContent = savedQuickFills.length + '/' + maxQuickFills;
                });
            }

            function managerStateMarkup(phrase, index) {
                if (!phrase.trim()) return '<div class="quick-fill-manager-state" id="quick-fill-manager-state-' + index + '">Enter a complete action phrase.</div>';
                var validity = quickFillValidity(phrase);
                if (validity.valid) {
                    return '<div class="quick-fill-manager-state is-valid" id="quick-fill-manager-state-' + index + '">Ready · ' + escapeHtml(quickFillSummary(validity.parsed)) + '</div>';
                }
                return '<div class="quick-fill-manager-state is-invalid" id="quick-fill-manager-state-' + index + '">Unavailable for the current ' + escapeHtml(quickFillOptions.race) + ' dominion · kept in this slot, but hidden from suggestions.</div>';
            }

            function renderQuickFillManager() {
                var body = document.getElementById('quickFillManagerBody');
                var available = quickFillDraft.filter(function (phrase) { return quickFillValidity(phrase).valid; }).length;
                body.innerHTML = '<p class="quick-fill-manager-summary"><strong>' + available + ' available for ' + escapeHtml(quickFillOptions.race) + '</strong> · ' + quickFillDraft.length + '/' + maxQuickFills + ' slots used.</p>' +
                    '<div class="alert alert-danger d-none" data-quick-fill-manager-error role="alert"></div>' +
                    '<div class="quick-fill-manager-list">' + (quickFillDraft.length ? quickFillDraft.map(function (phrase, index) {
                        return '<div class="quick-fill-manager-row" data-quick-fill-manager-row="' + index + '">' +
                            '<input class="form-control" type="text" value="' + escapeHtml(phrase) + '" data-quick-fill-manager-input="' + index + '" aria-label="Quick fill ' + (index + 1) + '" aria-describedby="quick-fill-manager-state-' + index + '" autocomplete="off" spellcheck="false">' +
                            '<div class="quick-fill-manager-tools" aria-label="Reorder or remove quick fill ' + (index + 1) + '">' +
                                '<button class="btn btn-sm btn-outline-secondary" type="button" data-quick-fill-up="' + index + '" aria-label="Move quick fill ' + (index + 1) + ' up" ' + (index === 0 ? 'disabled' : '') + '><i class="fa fa-arrow-up"></i></button>' +
                                '<button class="btn btn-sm btn-outline-secondary" type="button" data-quick-fill-down="' + index + '" aria-label="Move quick fill ' + (index + 1) + ' down" ' + (index === quickFillDraft.length - 1 ? 'disabled' : '') + '><i class="fa fa-arrow-down"></i></button>' +
                                '<button class="btn btn-sm btn-outline-danger" type="button" data-quick-fill-remove="' + index + '" aria-label="Remove quick fill ' + (index + 1) + '"><i class="fa fa-times"></i></button>' +
                            '</div>' + managerStateMarkup(phrase, index) + '</div>';
                    }).join('') : '<p class="text-body-secondary">No personal quick fills. Contextual suggestions still appear as you type.</p>') + '</div>' +
                    '<button class="btn btn-outline-primary w-100 mt-2" type="button" data-quick-fill-add ' + (quickFillDraft.length >= maxQuickFills ? 'disabled' : '') + '><i class="fa fa-plus me-1"></i> Add quick fill</button>';
            }

            $('#quickFillManagerModal').on('show.bs.modal', function () {
                quickFillDraft = savedQuickFills.slice();
                renderQuickFillManager();
            });

            document.getElementById('quickFillManagerBody').addEventListener('input', function (event) {
                var input = event.target.closest('[data-quick-fill-manager-input]');
                if (!input) return;
                var index = Number(input.dataset.quickFillManagerInput);
                quickFillDraft[index] = input.value;
                input.closest('[data-quick-fill-manager-row]').querySelector('.quick-fill-manager-state').outerHTML = managerStateMarkup(input.value, index);
            });

            document.getElementById('quickFillManagerBody').addEventListener('click', function (event) {
                var add = event.target.closest('[data-quick-fill-add]');
                var up = event.target.closest('[data-quick-fill-up]');
                var down = event.target.closest('[data-quick-fill-down]');
                var remove = event.target.closest('[data-quick-fill-remove]');
                var focusIndex = null;
                if (add && quickFillDraft.length < maxQuickFills) {
                    quickFillDraft.push('');
                    focusIndex = quickFillDraft.length - 1;
                } else if (up) {
                    var upIndex = Number(up.dataset.quickFillUp);
                    [quickFillDraft[upIndex - 1], quickFillDraft[upIndex]] = [quickFillDraft[upIndex], quickFillDraft[upIndex - 1]];
                    focusIndex = upIndex - 1;
                } else if (down) {
                    var downIndex = Number(down.dataset.quickFillDown);
                    [quickFillDraft[downIndex + 1], quickFillDraft[downIndex]] = [quickFillDraft[downIndex], quickFillDraft[downIndex + 1]];
                    focusIndex = downIndex + 1;
                } else if (remove) {
                    var removeIndex = Number(remove.dataset.quickFillRemove);
                    quickFillDraft.splice(removeIndex, 1);
                    focusIndex = Math.min(removeIndex, quickFillDraft.length - 1);
                } else return;
                renderQuickFillManager();
                if (focusIndex >= 0) requestAnimationFrame(function () {
                    document.querySelector('[data-quick-fill-manager-input="' + focusIndex + '"]').focus();
                });
            });

            document.getElementById('quickFillManagerSave').addEventListener('click', function () {
                var cleaned = quickFillDraft.map(function (phrase) { return phrase.trim(); }).filter(Boolean).slice(0, maxQuickFills);
                var normalized = cleaned.map(function (phrase) { return phrase.toLowerCase(); });
                if (new Set(normalized).size !== normalized.length) {
                    var error = document.querySelector('[data-quick-fill-manager-error]');
                    error.textContent = 'Each quick fill must be unique.';
                    error.classList.remove('d-none');
                    return;
                }
                savedQuickFills = cleaned;
                persistQuickFills();
                updateQuickFillCounts();
                document.dispatchEvent(new CustomEvent('quickfillschange'));
                document.querySelector('#quickFillManagerModal [data-bs-dismiss="modal"]').click();
            });

            updateQuickFillCounts();

            function clearDropIndicators() {
                document.querySelectorAll('.action-display-row.is-dragging, .action-display-row.drop-before, .action-display-row.drop-after').forEach(function (row) {
                    row.classList.remove('is-dragging', 'drop-before', 'drop-after');
                });
            }

            document.addEventListener('pointerdown', function (event) {
                var handle = event.target.closest('[data-drag-handle]');
                if (!handle || handle.disabled || event.button !== 0) return;
                var row = handle.closest('[data-action-row]');
                dragState = {
                    handle: handle,
                    row: row,
                    pointerId: event.pointerId,
                    startX: event.clientX,
                    startY: event.clientY,
                    moved: false,
                    targetRow: null,
                    position: 'before'
                };
                handle.setPointerCapture(event.pointerId);
            });

            document.addEventListener('pointermove', function (event) {
                if (!dragState || event.pointerId !== dragState.pointerId) return;
                if (!dragState.moved && Math.hypot(event.clientX - dragState.startX, event.clientY - dragState.startY) < 5) return;
                dragState.moved = true;
                event.preventDefault();
                clearDropIndicators();
                dragState.row.classList.add('is-dragging');
                if (event.clientY < 72) window.scrollBy(0, -8);
                else if (event.clientY > window.innerHeight - 72) window.scrollBy(0, 8);
                var targetRow = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-action-row]');
                if (!targetRow || targetRow.dataset.tick !== dragState.row.dataset.tick || targetRow === dragState.row) {
                    dragState.targetRow = null;
                    return;
                }
                var bounds = targetRow.getBoundingClientRect();
                dragState.targetRow = targetRow;
                dragState.position = event.clientY < bounds.top + bounds.height / 2 ? 'before' : 'after';
                targetRow.classList.add(dragState.position === 'before' ? 'drop-before' : 'drop-after');
            }, {passive: false});

            function finishDrag(event, cancelled) {
                if (!dragState) return;
                var state = dragState;
                dragState = null;
                clearDropIndicators();
                if (state.handle.hasPointerCapture(event.pointerId)) state.handle.releasePointerCapture(event.pointerId);
                if (cancelled || !state.moved || !state.targetRow) return;
                var sourceIndex = Number(state.row.dataset.actionIndex);
                var targetIndex = Number(state.targetRow.dataset.actionIndex);
                var insertionIndex = targetIndex + (state.position === 'after' ? 1 : 0);
                if (sourceIndex < insertionIndex) insertionIndex--;
                var rowCount = state.row.parentElement.querySelectorAll('[data-action-row]').length;
                insertionIndex = Math.max(0, Math.min(rowCount - 1, insertionIndex));
                var form = state.row.querySelector('form[id^="reorder-"]');
                form.querySelector('[data-reorder-target]').value = insertionIndex;
                form.requestSubmit();
            }

            document.addEventListener('pointerup', function (event) { finishDrag(event, false); });
            document.addEventListener('pointercancel', function (event) { finishDrag(event, true); });
            document.addEventListener('keydown', function (event) {
                var handle = event.target.closest('[data-drag-handle]');
                if (!handle || !event.altKey || !['ArrowUp', 'ArrowDown'].includes(event.key)) return;
                event.preventDefault();
                var row = handle.closest('[data-action-row]');
                var sourceIndex = Number(row.dataset.actionIndex);
                var targetIndex = sourceIndex + (event.key === 'ArrowUp' ? -1 : 1);
                var rowCount = row.parentElement.querySelectorAll('[data-action-row]').length;
                if (targetIndex < 0 || targetIndex >= rowCount) return;
                var form = row.querySelector('form[id^="reorder-"]');
                form.querySelector('[data-reorder-target]').value = targetIndex;
                form.requestSubmit();
            });
        })(jQuery);
    </script>
@endpush
