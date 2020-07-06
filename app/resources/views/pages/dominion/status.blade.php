@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> The Dominion of {{ $selectedDominion->name }}</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Overview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Ruler:</td>
                                        <td>{{ $selectedDominion->ruler_name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Race:</td>
                                        <td>{{ $selectedDominion->race->name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Land:</td>
                                        <td>{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("peasants") }}">
                                                Peasants:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->peasants) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("employment") }}">
                                                Employment:
                                            </span>
                                        </td>
                                        <td>{{ number_format($populationCalculator->getEmploymentPercentage($selectedDominion), 2) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("networth") }}">
                                                Networth:
                                            </span>
                                        </td>
                                        <td>{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("prestige") }}">
                                                Prestige:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->prestige) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Resources</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("platinum") }}">
                                                Platinum:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_platinum) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("food") }}">
                                                Food:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_food) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("lumber") }}">
                                                Lumber:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_lumber) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("mana") }}">
                                                Mana:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_mana) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("ore") }}">
                                                Ore:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_ore) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("gems") }}">
                                                Gems:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_gems) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("tech") }}">
                                                Research Points:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->resource_tech) }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getResourceHelpString("boats") }}">
                                                Boats:
                                            </span>
                                        </td>
                                        <td>{{ number_format(floor($selectedDominion->resource_boats + $queueService->getInvasionQueueTotalByResource($selectedDominion, "resource_boats"))) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Military</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $miscHelper->getGeneralHelpString("morale") }}">
                                                Morale:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->morale) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $selectedDominion->race, true) }}">
                                                Draftees:
                                            </span>
                                        </td>
                                        <td>{{ number_format($selectedDominion->military_draftees) }}</td>
                                    </tr>
                                    @foreach ($unitHelper->getUnitTypes() as $unitType)
                                        @php
                                            $unit = $selectedDominion->race->units->filter(function ($unit) use ($unitType) {
                                                return ($unit->slot == (int)str_replace('unit', '', $unitType));
                                            })->first();
                                        @endphp
                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race, true) }}">
                                                    {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}:
                                                </span>
                                            </td>
                                            @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
                                                <td>
                                                    {{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unit->slot)) }}
                                                </td>
                                            @else
                                                <td>
                                                    {{ number_format($selectedDominion->{'military_' . $unitType}) }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                            </tbody>
                        </table>
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
                <p>This section gives you a quick overview of your dominion.</p>
                <p>Your total land size is {{ number_format($landCalculator->getTotalLand($selectedDominion)) }} and networth is {{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}.</p>
                <p><a href="{{ route('dominion.advisors.rankings') }}">My Rankings</a></p>
            </div>
        </div>

        @if ($selectedDominion->pack !== null)
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Pack</h3>
                </div>
                <div class="box-body">
                    <p>You are in pack <em>{{$selectedDominion->pack->name}}</em> with:</p>
                    <ul>
                        @foreach ($selectedDominion->pack->dominions as $dominion)
                            <li>
                                @if ($dominion->ruler_name === $dominion->name)
                                    <strong>{{ $dominion->name }}</strong>
                                @else
                                    {{ $dominion->ruler_name }} of <strong>{{ $dominion->name }}</strong>
                                @endif

                                @if($dominion->ruler_name !== $dominion->user->display_name)
                                    ({{ $dominion->user->display_name }})
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p>
                        Slots used: {{ $selectedDominion->pack->dominions->count() }} / {{ $selectedDominion->pack->size }}.
                        @if ($selectedDominion->pack->isFull())
                            (full)
                        @elseif ($selectedDominion->pack->isClosed())
                            (closed)
                        @endif
                    </p>
                    @if (!$selectedDominion->pack->isFull() && !$selectedDominion->pack->isClosed())
                        <p>Your pack will automatically close on <strong>{{ $selectedDominion->pack->getClosingDate() }}</strong> to make space for random players in your realm.</p>
                        @if ($selectedDominion->pack->creator_dominion_id === $selectedDominion->id)
                            <p>
                                <form action="{{ route('dominion.misc.close-pack') }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-link" style="padding: 0;">Close Pack Now</button>
                                </form>
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if ($selectedDominion->realm->motd && ($selectedDominion->realm->motd_updated_at > now()->subDays(3)))
        <div class="col-sm-12 col-md-9">
            <div class="panel panel-info">
                <div class="panel-body">
                    <b>Message of the Day:</b> {{ $selectedDominion->realm->motd }}
                    <br/><small class="text-muted">Posted {{ $selectedDominion->realm->motd_updated_at }}</small>
                </div>
            </div>
        </div>
    @endif

    <div class="col-sm-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-newspaper-o"></i> Recent News</h3>
            </div>

            @if ($notifications->isEmpty())
                <div class="box-body">
                    <p>No recent news.</p>
                </div>
            @else
                <div class="box-body">
                    <table class="table table-condensed no-border">
                        @foreach ($notifications as $notification)
                            @php
                                $route = array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.route", '#');

                                if (is_callable($route)) {
                                    if (isset($notification->data['data']['_routeParams'])) {
                                        $route = $route($notification->data['data']['_routeParams']);
                                    } else {
                                        // fallback
                                        $route = '#';
                                    }
                                }
                                @endphp
                                <tr>
                                    <td>
                                        <span class="text-muted">{{ $notification->created_at }}</span>
                                    </td>
                                    <td>
                                        @if ($route !== '#')<a href="{{ $route }}">@endif
                                            <i class="{{ array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.iconClass", 'fa fa-question') }}"></i>
                                            {{ $notification->data['message'] }}
                                        @if ($route !== '#')</a>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            {{ $notifications->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            @if ($dominionProtectionService->isUnderProtection($selectedDominion))
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-shield text-aqua"></i> Under Protection</h3>
                    </div>
                    <div class="box-body">
                        <p>You are under a magical state of protection. During this time you cannot be attacked or attack other dominions. Nor can you cast any offensive spells or engage in espionage.</p>
                        <p>You have <b>{{ $selectedDominion->protection_ticks_remaining }}</b> ticks remaining.</p>
                        @php
                            $hoursLeft = $dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion);
                        @endphp
                        @if ($hoursLeft > 0)
                            <p>You will remain in protection until the fourth day of the round ({{ $dominionProtectionService->getProtectionEndDate($selectedDominion)->format('l, jS \o\f F Y \a\t G:i') }}).</p>
                            <p>If you have not completed your protection prior to this time, you will be unable to leave for an additional 24 hours.</p>
                        @endif
                        <p>No production occurs until you have left protection.</p>
                        <p>Made a mistake? You can restart your dominion while under protection.</p>
                        <form id="restart-dominion" class="form" action="{{ route('dominion.misc.restart') }}" method="post">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">Race:</label>
                                <select name="race" class="form-control">
                                    @foreach ($races as $race)
                                        <option value="{{ $race->id }}" {{ $selectedDominion->race_id == $race->id ? 'selected' : null }}>
                                            {{ $race->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Dominion Name:</label>
                                <input name="dominion_name" class="form-control" type="text" placeholder="{{ $selectedDominion->name }}" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ruler Name:</label>
                                <input name="ruler_name" class="form-control" type="text" placeholder="{{ $selectedDominion->ruler_name }}" />
                            </div>
                            <div class="form-group">
                                <select name="confirm" class="form-control">
                                    <option value="0">Restart?</option>
                                    <option value="1">Confirm Restart</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-sm btn-primary" disabled>Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#restart-dominion select[name=confirm]').change(function() {
                var confirm = $(this).val();
                if (confirm == "1") {
                    $('#restart-dominion button').prop('disabled', false);
                } else {
                    $('#restart-dominion button').prop('disabled', true);
                }
            });
        })(jQuery);
    </script>
@endpush
