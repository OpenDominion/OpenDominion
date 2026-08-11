<section class="automation-studio card card-primary" aria-labelledby="automation-studio-title">
    <header class="automation-studio-header card-header">
        <div>
            <h1 class="card-title mb-0" id="automation-studio-title">
                <i class="ra ra-robot-arm" aria-hidden="true"></i> Automated Actions
            </h1>
            <p>Schedule exact game actions for the next {{ $maxScheduleHours }} ticks.</p>
        </div>
    </header>

    <div class="automation-status-strip">
        <div class="automation-current-tick">
            <strong>Current Tick</strong>
            <span>Day {{ $selectedDominion->round->daysInRound() }}, Hour {{ $selectedDominion->round->hoursInDay() }}</span>
        </div>
        <div class="automation-quota" aria-label="{{ $usedAutomationCount }} of {{ $allowedActions }} daily automation ticks used">
            <div class="automation-quota-copy">
                <span>Daily automation ticks</span>
                <strong>{{ $usedAutomationCount }} / {{ $allowedActions }} used</strong>
            </div>
            <div class="automation-quota-track" aria-hidden="true">
                @foreach (range(1, $allowedActions) as $segment)
                    <i class="{{ $segment <= $usedAutomationCount ? 'is-used' : null }}"></i>
                @endforeach
            </div>
        </div>
    </div>

    <div class="automation-workspace">
        <div class="automation-schedule">
            @if (!empty($currentTickActions))
                <section class="automation-current-queue mb-3" aria-labelledby="current-queue-title">
                    <h2 class="automation-section-label" id="current-queue-title">Pending current tick</h2>
                    @include('partials.dominion.automation.tick-card', [
                        'tick' => $currentTick,
                        'hours' => 0,
                        'day' => $selectedDominion->round->daysInRound(),
                        'hour' => $selectedDominion->round->hoursInDay(),
                        'actions' => $currentTickActions,
                    ])
                </section>
            @endif

            <h2 class="automation-section-label">Next {{ $maxScheduleHours }} ticks</h2>
            <div class="automation-timeline">
                @foreach (range(1, $maxScheduleHours) as $hours)
                    @php
                        $tick = $currentTick + $hours;
                        $tickAt = $actionStartDate->copy()->addHours($hours);
                        $day = $selectedDominion->round->daysInRound($tickAt);
                        $hour = $selectedDominion->round->hoursInDay($tickAt);
                        $actions = array_values($automationConfig[$tick] ?? []);
                        $isOccupied = !empty($actions);
                        $openTickFormVisible = intval(old('tick')) === $tick;
                    @endphp
                    <div class="automation-tick-row {{ $isOccupied ? 'is-occupied' : null }}" data-ledger-tick="{{ $tick }}">
                        <div class="automation-tick-time" aria-hidden="true">
                            <strong>+{{ $hours }}</strong>
                            <span>D{{ $day }} · H{{ $hour }}</span>
                            <time datetime="{{ $tickAt->toIso8601String() }}">{{ $tickAt->format('H:i') }}</time>
                        </div>
                        <div class="automation-tick-mark" aria-hidden="true"></div>
                        <div class="automation-tick-content">
                            @if ($isOccupied)
                                @include('partials.dominion.automation.tick-card', [
                                    'tick' => $tick,
                                    'hours' => $hours,
                                    'day' => $day,
                                    'hour' => $hour,
                                    'actions' => $actions,
                                ])
                            @else
                                <button class="automation-open-tick" type="button"
                                    aria-expanded="{{ $openTickFormVisible ? 'true' : 'false' }}"
                                    aria-controls="open-tick-form-{{ $tick }}"
                                    onclick="toggleOpenTick({{ $tick }}, this)" {{ $isLocked ? 'disabled' : null }}>
                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                    <span>Open hour · schedule Day {{ $day }}, Hour {{ $hour }}</span>
                                </button>
                                <div class="automation-open-tick-form" id="open-tick-form-{{ $tick }}"
                                    style="display: {{ $openTickFormVisible ? 'block' : 'none' }};">
                                    <form action="{{ route('dominion.bonuses.actions') }}" method="post">
                                        @csrf
                                        <input type="hidden" name="tick" value="{{ $tick }}" />
                                        @include('partials.dominion.automation.action-form', [
                                            'formId' => "open-tick-action-form-{$tick}",
                                            'item' => null,
                                            'showTick' => false,
                                        ])
                                        <div class="mt-2 text-end">
                                            <button type="button" class="btn btn-sm btn-dark" onclick="toggleOpenTick({{ $tick }})">Cancel</button>
                                            <button type="submit" class="btn btn-sm btn-primary" {{ $isLocked ? 'disabled' : null }}>Save</button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="automation-playbooks" aria-labelledby="automation-playbooks-title">
            <section class="automation-playbook-panel">
                <header class="automation-playbook-head">
                    <h2 id="automation-playbooks-title">Saved Templates {{ collect($automationTemplates)->filter()->count() }} / {{ $maxTemplateSlots }}</h2>
                    <p>Each template stores exact actions at relative offsets.</p>
                </header>
                <div class="automation-playbook-list">
                    @foreach ($automationTemplates as $slot => $template)
                        @php
                            $templateTickCount = $template ? count($template['ticks']) : 0;
                            $templateActionCount = $template ? collect($template['ticks'])->sum(function ($templateTick) {
                                return count($templateTick['actions']);
                            }) : 0;
                        @endphp
                        <article class="automation-template {{ $template ? null : 'is-empty' }}" data-slot="{{ $slot + 1 }}">
                            @if ($template)
                                <div class="automation-template-copy">
                                    <h3>{{ $template['name'] }}</h3>
                                    <p>{{ $templateTickCount }} {{ $templateTickCount === 1 ? 'tick' : 'ticks' }} · {{ $templateActionCount }} exact {{ $templateActionCount === 1 ? 'action' : 'actions' }}</p>
                                </div>
                                <form class="automation-template-delete" action="{{ route('dominion.bonuses.actions.templates') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="operation" value="delete" />
                                    <input type="hidden" name="slot" value="{{ $slot }}" />
                                    <button class="automation-action-tool is-danger" type="submit" title="Delete template"
                                        aria-label="Delete template {{ $template['name'] }}" {{ $isLocked ? 'disabled' : null }}>
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <div class="template-offsets" aria-label="Relative tick offsets">
                                    @foreach ($template['ticks'] as $templateTick)
                                        <span>+{{ $templateTick['offset'] }}</span>
                                    @endforeach
                                </div>
                                <div class="automation-template-actions">
                                    <button class="btn btn-sm btn-primary" type="button"
                                        data-bs-toggle="modal" data-bs-target="#loadTemplateModal-{{ $slot }}"
                                        {{ $isLocked ? 'disabled' : null }}>Load template</button>
                                    <button class="btn btn-sm automation-template-replace" type="button" title="Replace template"
                                        aria-label="Replace template {{ $template['name'] }}"
                                        data-bs-toggle="modal" data-bs-target="#saveTemplateModal-{{ $slot }}"
                                        {{ $isLocked ? 'disabled' : null }}>
                                        <i class="fa fa-rotate" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @else
                                <div class="automation-template-empty-copy">
                                    <strong>Empty template slot</strong>
                                    <p>Save the current schedule here.</p>
                                </div>
                                <button class="btn btn-sm automation-template-save" type="button"
                                    data-bs-toggle="modal" data-bs-target="#saveTemplateModal-{{ $slot }}"
                                    {{ $isLocked || !$hasTemplateEligibleActions ? 'disabled' : null }}>
                                    <i class="fa fa-plus me-1" aria-hidden="true"></i> Save here
                                </button>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <details class="automation-execution-rules" open>
                <summary>Execution rules</summary>
                <div>
                    <ul>
                        <li>A requested amount is a ceiling. If fewer can be afforded or are available at execution, the lower amount is used.</li>
                        <li>Up to {{ $maxActionsPerTick }} ordered actions may run in a tick.</li>
                        <li>A tick with any non-bonus action uses one daily automation, regardless of its number of actions.</li>
                        <li>Actions are performed ~30 minutes into the hour.</li>
                    </ul>
                </div>
            </details>
        </aside>
    </div>
</section>
