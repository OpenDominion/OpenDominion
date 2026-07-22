@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    @php
        $currentTick = $selectedDominion->round->getTick();
        $actionStartDate = $selectedDominion->round->hasStarted()
            ? now()->startOfHour()
            : $selectedDominion->round->start_date;
        $isLocked = $selectedDominion->isLocked();
        $automationConfig = $selectedDominion->ai_enabled
            ? ($selectedDominion->ai_config ?? [])
            : [];
        $usedAutomationCount = max(0, min($allowedActions, $allowedActions - $selectedDominion->daily_actions));
        $currentTickActions = array_values($automationConfig[$currentTick] ?? []);
        $hasTemplateEligibleActions = collect($automationConfig)->contains(function ($actions, $tick) use ($currentTick, $maxScheduleHours) {
            $offset = intval($tick) - $currentTick;

            return $offset >= 1 && $offset <= $maxScheduleHours && !empty($actions);
        });
    @endphp
    @include('partials.dominion.automation.ledger-content')

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
                            value="{{ $template['name'] ?? '' }}" placeholder="e.g. Troop training" required>
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
                                            <small>Replace future ticks. A pending +0 tick is kept unless the template fills all three paid automation slots; Daily Bonus does not use a slot.</small>
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
    @include('partials.dominion.automation.ledger-styles')
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

            window.toggleOpenTick = function (tick, trigger) {
                var form = $('#open-tick-form-' + tick);
                form.toggle();
                var expanded = form.is(':visible');
                var control = trigger
                    ? $(trigger)
                    : $('[aria-controls="open-tick-form-' + tick + '"]');
                control.attr('aria-expanded', String(expanded));
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
                if (!dragState || event.pointerId !== dragState.pointerId) return;
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
