@extends('layouts.master')

@section('page-header', 'The Realm')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-circle-of-circles"></i> {{ $realm->name }} (#{{ $realm->number }})</h3>
                </div>
                <div class="box-body table-responsive no-padding">

                    <table class="table">
                        <colgroup>
                            <col width="50">
                            <col>
                            @if ($isOwnRealm || $round->hasEnded())
                                <col width="200">
                            @endif
                            <col width="100">
                            <col width="100">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Dominion</th>
                                @if ($isOwnRealm || $round->hasEnded())
                                    <th class="text-center">Player</th>
                                @endif
                                <th class="text-center">Race</th>
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < max($round->realm_size, $dominions->count()); $i++)
                                @php
                                    $dominion = $dominions->get($i);
                                @endphp

                                @if ($dominion === null)
                                    <tr>
                                        <td>&nbsp;</td>
                                        @if ($isOwnRealm || $round->hasEnded())
                                            <td colspan="5"><i>Vacant</i></td>
                                        @else
                                            <td colspan="4"><i>Vacant</i></td>
                                        @endif
                                    </tr>
                                @else
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            @if ($dominion->isCourtMember())
                                                @php
                                                    $role = $governmentHelper->getCourtAppointment($dominion->getCourtSeat());
                                                @endphp
                                                <i class="{{ $role['icon'] }} ra-lg text-{{ $role['icon-color'] }}" title="{{ $role['name'] }}" data-toggle="tooltip"></i>
                                            @endif

                                            @if ($protectionService->isUnderProtection($dominion))
                                                <i class="ra ra-shield ra-lg text-aqua" title="Under Protection" data-toggle="tooltip"></i>
                                            @endif

                                            @if ($guardMembershipService->isEliteGuardMember($dominion))
                                                <i class="ra ra-heavy-shield ra-lg text-yellow" title="Elite Guard" data-toggle="tooltip"></i>
                                            @elseif ($guardMembershipService->isRoyalGuardMember($dominion))
                                                <i class="ra ra-heavy-shield ra-lg text-green" title="Royal Guard" data-toggle="tooltip"></i>
                                            @endif

                                            @if ($guardMembershipService->isBlackGuardMember($dominion) && ((isset($dominion->settings['black_guard_icon']) && $dominion->settings['black_guard_icon'] == 'public') || $guardMembershipService->isBlackGuardMember($selectedDominion) || ($dominion->realm_id == $selectedDominion->realm_id)))
                                                <i class="ra ra-fire-shield ra-lg text-purple" title="Chaos League" data-toggle="tooltip"></i>
                                            @endif

                                            @if (isset($dominion->settings['show_icon']) && $dominion->settings['show_icon'] == 'on')
                                                {!! $rankingsHelper->getIconDisplay(isset($rankings[$dominion->id]) ? $rankings[$dominion->id] : [], isset($dominion->settings['preferred_title']) ? $dominion->settings['preferred_title'] : '') !!}
                                            @endif

                                            @if ($dominion->id === $selectedDominion->id)
                                                <b>{{ $dominion->name }}</b>
                                            @else
                                                @if ($isOwnRealm)
                                                    @if ($selectedDominion->inRealmAndSharesAdvisors($dominion))
                                                        <a href="{{ route('dominion.realm.advisors.op-center', $dominion) }}">{{ $dominion->name }}</a>
                                                    @else
                                                        {{ $dominion->name }}
                                                    @endif
                                                @else
                                                    <a href="{{ route('dominion.op-center.show', $dominion) }}">{{ $dominion->name }}</a>
                                                @endif
                                            @endif

                                            @if ($dominion->user == null)
                                                <span class="label label-info">Bot</span>
                                            @else
                                                @if ($isOwnRealm && $round->hasAssignedRealms() && !$round->hasEnded() && $dominion->user->isOnline())
                                                    <span class="label label-success">Online</span>
                                                @endif
                                            @endif

                                            @if ($dominion->locked_at !== null)
                                                <span class="label label-danger">Locked</span>
                                            @elseif ($dominion->isAbandoned())
                                                <span class="label label-warning">Abandoned</span>
                                            @endif

                                            @if (!$round->hasEnded() && $dominion->user_id == $selectedDominion->user_id)
                                                <a href="{{ route('dominion.misc.settings') }}" title="Icon Settings" data-toggle="tooltip">
                                                    <i class="fa fa-cog fa-lg"></i>
                                                </a>
                                            @endif
                                        </td>
                                        @if ($isOwnRealm || $round->hasEnded())
                                            @if ($dominion->user_id !== null && ($round->hasEnded() ||  ($isOwnRealm && $selectedDominion->inRealmAndSharesAdvisors($dominion) && $selectedDominion->sharesUsername($dominion))))
                                                <td class="text-center">
                                                    <a href="{{ route('valhalla.user', $dominion->user_id) }}"
                                                        @if ($isOwnRealm && !$round->hasEnded())
                                                            title="Last online {{ now()->longAbsoluteDiffForHumans($dominion->user->last_online, 2) }} ago" data-toggle="tooltip" data-placement="right"
                                                        @endif
                                                    >
                                                        {{ $dominion->user->display_name }}
                                                    </a>
                                                    @if ($isOwnRealm && ($dominion->user_id !== $selectedDominion->user_id) && ($round->hasEnded() && $round->end_date->addDays(7) > now()))
                                                        <a
                                                            href="{{ route('api.user.feedback') }}?user_id={{ $dominion->user_id }}&endorsed=1"
                                                            class="upvote_user{{ $selectedDominion->user->hasUpvotedUser($dominion->user_id) ? ' text-green' : null }}"
                                                            title="Upvote"
                                                            data-toggle="tooltip"
                                                        >
                                                            <i class="fa fa-thumbs-up" style="margin-left: 2px;"></i>
                                                        </a>
                                                        <a
                                                            href="{{ route('api.user.feedback') }}?user_id={{ $dominion->user_id }}&endorsed=0"
                                                            class="downvote_user{{ $selectedDominion->user->hasDownvotedUser($dominion->user_id) ? ' text-red' : null }}"
                                                            title="Downvote"
                                                            data-toggle="tooltip"
                                                        >
                                                            <i class="fa fa-thumbs-down" style="margin-left: 2px;"></i>
                                                        </a>
                                                    @endif
                                                </td>
                                            @else
                                                <td class="text-center"></td>
                                            @endif
                                        @endif
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
                                        </td>
                                        <td class="text-center">{{ number_format($landCalculator->getTotalLand($dominion)) }}</td>
                                        <td class="text-center">{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                                    </tr>
                                @endif
                            @endfor
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This is the realm <strong>{{ $realm->name }} (#{{ $realm->number }})</strong>.</p>
                    <p>Its alignment is <strong>{{ $realm->alignment }}</strong>, it contains <strong>{{ $dominions->count() }}</strong> {{ str_plural('dominion', $dominions->count()) }}, its networth is <strong>{{ number_format($networthCalculator->getRealmNetworth($realm)) }}</strong>, and it controls <strong>{{ number_format($landCalculator->getRealmLand($realm)) }}</strong> acres of land.</p>
                    @if ($realm->valor)
                        <p>This realm has earned <strong>{{ number_format($realm->valor) }}</strong> valor.</p>
                    @endif
                    @foreach ($realm->roundWonders as $wonder)
                        <p>This realm controls the <span class="text-orange">{{ $wonder->wonder->name }}</span>.</p>
                    @endforeach
                    <p><a href="{{ route('dominion.town-crier', [$realm->number]) }}">View Town Crier</a></p>
                    @if ($realm->id !== $selectedDominion->realm_id)
                        <p><a href="{{ route('dominion.realm') }}">My Realm</a></p>
                    @endif
                </div>
                @if (($prevRealm !== null) || ($nextRealm !== null))
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-xs-4">
                                @if ($prevRealm !== null)
                                    <a href="{{ route('dominion.realm', $prevRealm->number) }}">&lt; Previous</a><br>
                                    <small class="text-muted">{{ $prevRealm->name }} (# {{  $prevRealm->number }})</small>
                                @endif
                            </div>
                            <div class="col-xs-4">
                                <form action="{{ route('dominion.realm.change-realm') }}" method="post" role="form">
                                    @csrf
                                    <input type="number" name="realm" class="form-control text-center" placeholder="{{ $realm->number }}" min="0" max="{{ $realmCount - 1 }}">
                                </form>
                            </div>
                            <div class="col-xs-4 text-right">
                                @if ($nextRealm !== null)
                                    <a href="{{ route('dominion.realm', $nextRealm->number) }}">Next &gt;</a><br>
                                    <small class="text-muted">{{ $nextRealm->name }} (# {{  $nextRealm->number }})</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            @include('partials.dominion.join-discord')
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><i class="ra ra-crossed-axes"></i> War</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12 table-responsive">
                            <table class="table table-condensed">
                                <tr>
                                    <th>Realm</th>
                                    <th>Declared By</th>
                                    <th>Declared at</th>
                                    <th>Active at</th>
                                    <th>Inactive at</th>
                                    <th>War Bonus</th>
                                </tr>
                                @foreach ($realm->warsOutgoing()->active()->get() as $war)
                                    @php
                                        $activeHours = $governmentService->getHoursBeforeWarActive($war);
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('dominion.realm', [$war->targetRealm->number]) }}">{{ $war->targetRealm->name }} (#{{ $war->targetRealm->number }})</a></td>
                                        <td>#{{ $realm->number }}</td>
                                        <td>{{ $governmentService->getWarDeclaredAt($war) }}</td>
                                        <td>{{ $war->active_at }}</td>
                                        <td>{{ $war->inactive_at }}</td>
                                        <td>
                                            @if ($war->inactive_at != null)
                                                <span class="label label-success">Active</span>
                                                <span class="label label-danger">Expiring</span>
                                            @elseif ($activeHours == 0)
                                                <span class="label label-success">Active</span>
                                            @else
                                                <span class="label label-warning">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach ($realm->warsIncoming()->active()->get() as $war)
                                    @php
                                        $activeHours = $governmentService->getHoursBeforeWarActive($war);
                                    @endphp
                                    <tr>
                                        <td><a href="{{ route('dominion.realm', [$war->sourceRealm->number]) }}">{{ $war->sourceRealm->name }} (#{{ $war->sourceRealm->number }})</a></td>
                                        <td>#{{ $war->sourceRealm->number }}</td>
                                        <td>{{ $governmentService->getWarDeclaredAt($war) }}</td>
                                        <td>{{ $war->active_at }}</td>
                                        <td>{{ $war->inactive_at }}</td>
                                        <td>
                                            @if ($war->inactive_at != null)
                                                <span class="label label-success">Active</span>
                                                <span class="label label-danger">Expiring</span>
                                            @elseif ($activeHours == 0)
                                                <span class="label label-success">Active</span>
                                            @else
                                                <span class="label label-warning">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><i class="ra ra-pyramids ra-lg"></i> Wonder</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12 table-responsive">
                            <table class="table table-condensed">
                                <tr>
                                    <th>Name</th>
                                    <th>Power</th>
                                    <th>Description</th>
                                </tr>
                                @foreach ($realm->roundWonders as $wonder)
                                    <tr>
                                        <td>{{ $wonder->wonder->name }}</a></td>
                                        <td>
                                            @if ($wonder->realm_id == $selectedDominion->realm_id || $governmentService->isAtWar($selectedDominion->realm, $wonder->realm))
                                                {{ number_format($wonderCalculator->getCurrentPower($wonder)) }}
                                            @else
                                                ~{{ number_format($wonderCalculator->getApproximatePower($wonder)) }}
                                            @endif
                                            / {{ number_format($wonder->power) }}
                                        </td>
                                        <td>{{ $wonderHelper->getWonderDescription($wonder->wonder) }}</td>
                                    </tr>
                                @endforeach
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
                    <p>Here you view which realms currently have war relations with this one.</p>
                    <p>While a war is active, dominions in both realms gain 5% offense when attacking members of the opposing realm. If both realms have an active war bonus, that increases to 10% offense.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.upvote_user').click(function (e) {
                e.preventDefault();
                sendFeedback(e.delegateTarget, true);
            });

            $('.downvote_user').click(function (e) {
                e.preventDefault();
                sendFeedback(e.delegateTarget, false);
            });

            function sendFeedback(anchorElem, endorsed) {
                $.get(
                    anchorElem.href, {},
                    function(response) {
                        if (endorsed) {
                            $(anchorElem).siblings().removeClass('text-red');
                            $(anchorElem).addClass('text-green');
                        } else {
                            $(anchorElem).siblings().removeClass('text-green');
                            $(anchorElem).addClass('text-red');
                        }
                    }
                );
            }
        })(jQuery);
    </script>
@endpush
