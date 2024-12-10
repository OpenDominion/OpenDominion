@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-robot-arm"></i> Automated Actions</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            @php
                                $currentTick = $selectedDominion->round->getTick();
                                if ($selectedDominion->round->hasStarted()) {
                                    $actionStartDate = now()->startOfHour();
                                } else {
                                    $actionStartDate = $selectedDominion->round->start_date;
                                }
                            @endphp
                            <h4>Current Tick</h4>
                            <div style="margin-bottom: 20px;">
                                Day {{ $selectedDominion->round->daysInRound() }}, Hour {{ $selectedDominion->round->hoursInDay() }}
                            </div>
                            <h4>Configured Actions</h4>
                            <div>
                                @if (!$selectedDominion->ai_enabled || empty($selectedDominion->ai_config))
                                    <p><i>No automated actions scheduled.</i></p>
                                @else
                                    <table class="table table-condensed">
                                        <colgroup>
                                            <col width="15%">
                                            <col width="15%">
                                            <col width="15%">
                                            <col>
                                        </colgroup>
                                        <tr>
                                            <th>Ticks</th>
                                            <th>Day</th>
                                            <th>Hour</th>
                                            <th>Action</th>
                                        </tr>
                                        @foreach ($selectedDominion->ai_config as $tick => $config)
                                            @php
                                                $hours = $tick - $currentTick;
                                                $day = $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($hours));
                                                $hour = $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($hours));
                                            @endphp
                                            @foreach ($config as $index => $item)
                                                <tr>
                                                    <td>
                                                        +{{ $hours }}
                                                    </td>
                                                    <td>
                                                        {{ $day }}
                                                    </td>
                                                    <td>
                                                        {{ $hour }}
                                                    </td>
                                                    <td>
                                                        @if ($item['action'] == 'train')
                                                            Train
                                                            {{ $item['amount'] }}
                                                            {{ $unitHelper->getUnitName($item['key'], $selectedDominion->race) }}
                                                        @elseif ($item['action'] == 'construct')
                                                            Construct
                                                            {{ $item['amount'] }}
                                                            {{ $buildingHelper->getBuildingName($item['key']) }}
                                                        @elseif ($item['action'] == 'explore')
                                                            Explore
                                                            {{ $item['amount'] }}
                                                            {{ ucwords($item['key']) }}
                                                        @elseif ($item['action'] == 'rezone')
                                                            Rezone
                                                            {{ $item['amount'] }}
                                                            {{ ucwords($item['key']) }} to
                                                            {{ ucwords($item['key2']) }}
                                                        @elseif ($item['action'] == 'spell')
                                                            Cast
                                                            {{ $spellHelper->getSpellByKey($item['key'])->name }}
                                                        @elseif ($item['action'] == 'release')
                                                            Release
                                                            {{ $item['amount'] }}
                                                            Draftees
                                                        @elseif ($item['action'] == 'draft_rate')
                                                            Set Draft Rate
                                                            {{ $item['amount'] }}%
                                                        @elseif ($item['action'] == 'daily_bonus')
                                                            Daily Bonus
                                                            {{ ucwords($item['key']) }}
                                                        @endif
                                                        <form action="{{ route('dominion.bonuses.actions.delete') }}" method="post" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="tick" value="{{ $tick }}" />
                                                            <input type="hidden" name="key" value="{{ $index }}" />
                                                            <button class="btn btn-link no-padding pull-right" type="submit" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                                <i class="fa fa-trash text-danger"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </table>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <h4>Add Action</h4>
                            <form action="{{ route('dominion.bonuses.actions') }}" method="post" role="form">
                                @csrf
                                <div class="form-group">
                                    Tick:
                                    <select class="form-control" name="tick" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        @foreach (range(1, 12) as $hours)
                                            <option value="{{ $currentTick + $hours }}">
                                                Day {{ $selectedDominion->round->daysInRound($actionStartDate->copy()->addHours($hours)) }},
                                                Hour {{ $selectedDominion->round->hoursInDay($actionStartDate->copy()->addHours($hours)) }}
                                                (+{{ $hours }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    Action:
                                    <select class="form-control" name="action" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        <option value="train">Train Military</option>
                                        <option value="construct">Construct Buildings</option>
                                        <option value="explore">Explore Land</option>
                                        <option value="rezone">Rezone Land</option>
                                        <option value="spell">Cast Spell</option>
                                        <option value="release">Release Draftees</option>
                                        <option value="draft_rate">Set Draft Rate</option>
                                        <option value="daily_bonus">Daily Bonus</option>
                                    </select>
                                </div>
                                <div class="form-group action-options train">
                                    Unit:
                                    <select class="form-control" name="key" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        <option></option>
                                        @foreach ($unitTypes as $unitType)
                                            <option value="{{ $unitType }}">
                                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group action-options construct" style="display: none;">
                                    Building:
                                    <select class="form-control" name="key" disabled>
                                        <option></option>
                                        @foreach ($buildings as $building)
                                            <option value="{{ $building }}">
                                                {{ $buildingHelper->getBuildingName($building) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group action-options explore rezone" style="display: none;">
                                    Land Type:
                                    <select class="form-control" name="key" disabled>
                                        <option></option>
                                        @foreach ($landTypes as $landType)
                                            <option value="{{ $landType }}">
                                                {{ ucwords($landType) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group action-options rezone" style="display: none;">
                                    Target Land Type:
                                    <select class="form-control" name="key2" disabled>
                                        <option></option>
                                        @foreach ($landTypes as $landType)
                                            <option value="{{ $landType }}">
                                                {{ ucwords($landType) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group action-options train construct explore rezone release draft_rate">
                                    Amount:
                                    <input type="number" name="amount" class="form-control" placeholder="Amount" min="0" {{ $selectedDominion->isLocked() ? 'disabled' : null }} />
                                </div>
                                <div class="form-group action-options spell" style="display: none;">
                                    Spell:
                                    <select class="form-control" name="key" disabled>
                                        <option></option>
                                        @foreach ($spells as $spell)
                                            <option value="{{ $spell->key }}">
                                                {{ $spell->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group action-options daily_bonus" style="display: none;">
                                    Bonus:
                                    <select class="form-control" name="key" disabled>
                                        <option></option>
                                        <option value="land">Land</option>
                                        <option value="platinum">Platinum</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary pull-right" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    Save
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
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
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var actionSelectElement = $('select[name=action]');
            var actionContainerElements = $('.action-options');

            function toggleDropdowns(value) {
                actionContainerElements.hide();
                actionContainerElements.children('select,input').prop('disabled', true);

                var selectedParentElement = $("." + value);
                selectedParentElement.children('select,input').prop('disabled', false);
                selectedParentElement.show();
            }

            actionSelectElement.change(function (e) {
                toggleDropdowns(e.currentTarget.value);
            });
        })(jQuery);
    </script>
@endpush
