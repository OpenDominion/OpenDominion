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
                                    <button class="btn btn-sm btn-danger" type="button" title="Clear all actions"
                                        data-bs-toggle="modal" data-bs-target="#clearTickModal"
                                        data-tick="{{ $tick }}" data-label="Day {{ $day }}, Hour {{ $hour }} (+{{ $hours }})"
                                        {{ $isLocked ? 'disabled' : null }}>
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm mb-0">
                                        <colgroup>
                                            <col width="30px">
                                            <col>
                                            <col width="200px">
                                        </colgroup>
                                        @foreach ($actions as $index => $item)
                                            <tr class="action-display-row" id="display-{{ $tick }}-{{ $index }}">
                                                <td class="text-center text-muted align-middle">{{ $index + 1 }}.</td>
                                                <td class="align-middle">
                                                    @include('pages.dominion.automation._action-label', ['item' => $item])
                                                </td>
                                                <td class="text-end align-middle">
                                                    {{-- Reorder Up --}}
                                                    @if ($index > 0)
                                                        <form action="{{ route('dominion.bonuses.actions.reorder') }}" method="post" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="tick" value="{{ $tick }}" />
                                                            <input type="hidden" name="key" value="{{ $index }}" />
                                                            <input type="hidden" name="direction" value="up" />
                                                            <button class="btn btn-outline-secondary btn-sm" type="submit" title="Move up" {{ $isLocked ? 'disabled' : null }}>
                                                                <i class="fa fa-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="btn btn-sm invisible"><i class="fa fa-arrow-up"></i></span>
                                                    @endif
                                                    {{-- Reorder Down --}}
                                                    @if ($index < count($actions) - 1)
                                                        <form action="{{ route('dominion.bonuses.actions.reorder') }}" method="post" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="tick" value="{{ $tick }}" />
                                                            <input type="hidden" name="key" value="{{ $index }}" />
                                                            <input type="hidden" name="direction" value="down" />
                                                            <button class="btn btn-outline-secondary btn-sm" type="submit" title="Move down" {{ $isLocked ? 'disabled' : null }}>
                                                                <i class="fa fa-arrow-down"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="btn btn-sm invisible"><i class="fa fa-arrow-down"></i></span>
                                                    @endif
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
                                                                @foreach (range(1, 12) as $h)
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
                                        @foreach (range(1, 12) as $hours)
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
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    <p>You can schedule {{ $allowedActions }} automations per day, which reset with your daily bonuses.</p>
                    <p>Each tick that you automate can consist of up to 10 actions in sequence.</p>
                    <p>Actions cannot be scheduled more than 12 hours in advance and are performed ~30 minutes into the hour.</p>
                    <p>In the event that you do not have enough resources to perform an action, it will instead use the max that you can afford.</p>
                    <p>Taking your daily land and platinum bonuses will not count toward your daily automation limit.</p>
                    <p>You have <b>{{ $selectedDominion->daily_actions }}</b> automation(s) remaining today.</p>
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

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            // Action form toggle logic - scoped per form container
            function initActionForm(container) {
                var actionSelect = container.find('select[name=action]');
                var optionContainers = container.find('.action-options');

                function toggleDropdowns(value) {
                    optionContainers.hide();
                    optionContainers.children('select,input').prop('disabled', true);

                    var selected = container.find('.' + value);
                    selected.children('select,input').prop('disabled', false);
                    selected.show();
                }

                actionSelect.change(function (e) {
                    toggleDropdowns(e.currentTarget.value);
                });

                // Initialize with current value
                if (actionSelect.val()) {
                    toggleDropdowns(actionSelect.val());
                }
            }

            // Initialize all existing forms on page load
            $('.action-form-container').each(function () {
                initActionForm($(this));
            });

            // Expose for dynamic init
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
            // Clear tick modal
            $('#clearTickModal').on('show.bs.modal', function (e) {
                var button = $(e.relatedTarget);
                $('#clearTickValue').val(button.data('tick'));
                $('#clearTickLabel').text(button.data('label'));
            });
        })(jQuery);
    </script>
@endpush
