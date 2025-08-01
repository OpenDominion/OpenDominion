@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> The Dominion of {{ $selectedDominion->name }} (#{{ $selectedDominion->realm->number }})</h3>
                </div>
                <div class="box-body no-padding">
                    @include('partials.dominion.info.status', ['data' => $infoMapper->mapStatus($selectedDominion, false), 'race' => $selectedDominion->race, ])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            @if ($protectionService->isUnderProtection($selectedDominion))
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-shield text-aqua"></i> Under Protection</h3>
                    </div>
                    <div class="box-body">
                        <p><a href="{{ route('dominion.misc.restart') }}" class="btn btn-success">Restart or Rename</a></p>
                        <p>You are under a magical state of protection. During this time you cannot be attacked or attack other dominions. Nor can you cast any offensive spells or engage in espionage.</p>
                        @php
                            $hoursLeft = $protectionService->getUnderProtectionHoursLeft($selectedDominion);
                        @endphp
                        @if ($hoursLeft > 0)
                            <p>You will remain in protection until the start of the round ({{ $selectedDominion->round->start_date->format('l, jS \o\f F Y \a\t G:i') }}).</p>
                            <p>If you have not completed your protection prior to this time, you will be unable to leave for an additional 24 hours.</p>
                        @endif
                        @if (!$selectedDominion->round->hasStarted())
                            <p>Have a log from the <a href="https://github.com/Yami-10/OD-Simulator" target="_blank">Excel Simulator</a>?</p>
                            <p><a href="{{ route('dominion.protection.import-log') }}" class="btn btn-primary">Import Log</a></p>
                        @endif
                    </div>
                </div>
            @else
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Information</h3>
                    </div>
                    <div class="box-body">
                        <p>This section gives you a quick overview of your dominion.</p>

                        <table class="table table-condensed" style="margin-bottom: 10px;">
                            <thead>
                                <tr>
                                    <th>{{ $selectedDominion->race->name }} Perks</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedDominion->race->perks as $perk)
                                    @php
                                        $perkDescription = $raceHelper->getPerkDescriptionHtmlWithValue($perk);
                                    @endphp
                                    <tr>
                                        <td>
                                            {!! $perkDescription['description'] !!}
                                        </td>
                                        <td class="text-center">
                                            {!! $perkDescription['value']  !!}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="text-center">
                            <a href="{{ route('dominion.advisors.rankings') }}">My Rankings</a>
                        </div>
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
                            <colgroup>
                                <col width="150">
                                <col>
                            </colgroup>
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

        @if ($selectedDominion->pack !== null)
            <div class="col-sm-12 col-md-3">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Pack</h3>
                    </div>
                    <div class="box-body">
                        <p>You are a member of <b>{{ $selectedDominion->pack->name }}</b> ({{ $selectedDominion->pack->password }}) with:</p>
                        <ul>
                            @foreach ($selectedDominion->pack->dominions as $dominion)
                                <li>
                                    @if ($dominion->ruler_name === $dominion->name)
                                        <strong>{{ $dominion->name }}</strong>
                                    @else
                                        {{ $dominion->ruler_name }} of <strong>{{ $dominion->name }}</strong>
                                    @endif

                                    @if ($dominion->ruler_name !== $dominion->user->display_name)
                                        ({{ $dominion->user->display_name }})
                                    @endif

                                    - {{ $dominion->race->name }}
                                </li>
                            @endforeach
                        </ul>
                        <p>
                            Slots used: {{ $selectedDominion->pack->dominions->count() }} / {{ $selectedDominion->pack->size }}
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
                @include('partials.dominion.join-discord')
            </div>
        @elseif (!$selectedDominion->round->hasAssignedRealms())
            <div class="col-sm-12 col-md-3">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Join a Pack</h3>
                    </div>
                    <form action="{{ route('dominion.misc.join-pack') }}" method="post">
                        @csrf
                        <div class="box-body">
                            <div class="form-group">
                                <input type="text" name="pack_name" id="pack_name" class="form-control" placeholder="Pack Name" required>
                            </div>
                            <div class="form-group">
                                <input type="text" name="pack_password" id="pack_password" class="form-control" placeholder="Pack Password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Join</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="col-sm-12 col-md-3">
                @include('partials.dominion.join-discord')
            </div>
        @endif
    </div>
@endsection
