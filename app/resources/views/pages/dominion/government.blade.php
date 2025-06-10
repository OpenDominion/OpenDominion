@extends('layouts.master')

@section('page-header', 'Government')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-queen-crown"></i> Monarchy</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        @if ($selectedDominion->isMonarch() || $selectedDominion->isJester())
                            <div class="col-md-12">
                                <form action="{{ route('dominion.government.realm') }}" method="post" role="form">
                                    @csrf
                                    <label for="realm_motd">Realm Message</label>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <input type="text" class="form-control" name="realm_motd" id="realm_motd" value="{{ $selectedDominion->realm->motd }}" maxlength="256" autocomplete="off" {{ $selectedDominion->isLocked() ? 'disabled' : null }} />
                                            </div>
                                        </div>
                                    </div>
                                    <label for="realm_name">Realm Name</label>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <input type="text" class="form-control" name="realm_name" id="realm_name" value="{{ $selectedDominion->realm->name }}" maxlength="64" autocomplete="off" {{ $selectedDominion->isLocked() ? 'disabled' : null }} />
                                            </div>
                                        </div>
                                        <div class="col-xs-offset-6 col-xs-6 col-sm-offset-8 col-sm-4 col-lg-offset-10 col-lg-2">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    Change
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-md-12">
                                <hr/>
                            </div>
                        @endif
                        <div class="col-md-12">
                            <form action="{{ route('dominion.government.monarch') }}" method="post" role="form">
                                @csrf
                                <label for="monarch">Vote for monarch</label>
                                <div class="row">
                                    <div class="col-sm-8 col-lg-10">
                                        <div class="form-group">
                                            <select name="monarch" id="monarch" class="form-control select2 dominion_list" required style="width: 100%" data-placeholder="Select a dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                <option></option>
                                                @foreach ($dominions as $dominion)
                                                    <option value="{{ $dominion->id }}"
                                                            data-race="{{ $dominion->race->name }}"
                                                            data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                            data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                            data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}">
                                                        {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-offset-6 col-xs-6 col-sm-offset-0 col-sm-4 col-lg-2">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                Vote
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <form action="{{ route('dominion.government.advisors') }}" method="post" role="form">
                                @csrf
                                <div class="form-group table-responsive">
                                    <table class="table table-condensed">
                                        <colgroup>
                                            <col>
                                            <col>
                                            <col width="200">
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th>Dominion</th>
                                                <th>Voted for</th>
                                                <th>Player</th>
                                                <th>Advisors</th>
                                            </tr>
                                        </thead>
                                        @php
                                            $dominionAdvisors = $selectedDominion->getSetting("realmadvisors");
                                            $realmAdvisors = $selectedDominion->user->getSetting("realmadvisors");
                                            $packAdvisors = $selectedDominion->user->getSetting("packadvisors");
                                        @endphp
                                        @foreach ($dominions as $dominion)
                                            <tr>
                                                <td>
                                                    @if ($dominion->isMonarch())
                                                        <span class="text-red">{{ $dominion->name }}</span>
                                                    @else
                                                        {{ $dominion->name }}
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $dominion->monarchVote ? $dominion->monarchVote->name : '--' }}</td>
                                                </td>
                                                <td>
                                                    @if ($dominion->user_id !== null && $selectedDominion->inRealmAndSharesAdvisors($dominion) && $selectedDominion->sharesUsername($dominion))
                                                        {{ $dominion->user->display_name }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($dominion->user_id !== null && $dominion->id !== $selectedDominion->id)
                                                        @if ($dominionAdvisors && array_key_exists($dominion->id, $dominionAdvisors) && $dominionAdvisors[$dominion->id] === true)
                                                            <input type="checkbox" name="realmadvisors[]" value="{{ $dominion->id }}" checked="checked">
                                                        @elseif ($dominionAdvisors && array_key_exists($dominion->id, $dominionAdvisors) && $dominionAdvisors[$dominion->id] === false)
                                                            <input type="checkbox" name="realmadvisors[]" value="{{ $dominion->id }}">
                                                        @elseif ($packAdvisors !== false && $selectedDominion->pack_id !== null && $selectedDominion->pack_id == $dominion->pack_id)
                                                            <input type="checkbox" name="realmadvisors[]" value="{{ $dominion->id }}" checked="checked">
                                                        @elseif ($dominion->created_at > $dominion->round->realmAssignmentDate())
                                                            <input type="checkbox" name="realmadvisors[]" value="{{ $dominion->id }}">
                                                        @elseif ($realmAdvisors === false)
                                                            <input type="checkbox" name="realmadvisors[]" value="{{ $dominion->id }}">
                                                        @else
                                                            <input type="checkbox" name="realmadvisors[]" value="{{ $dominion->id }}" checked="checked">
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                                <div class="row">
                                    <div class="col-xs-offset-6 col-xs-6 col-sm-offset-8 col-sm-4 col-lg-offset-10 col-lg-2">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
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
                    <p>Here you can vote for the monarch of your realm. You can change your vote at any time.</p>
                    <p>The monarch has the power to declare war, update the realm's message of the day, and moderate council posts.</p>
                    <p>You can share your advisors with other members of your realm. Players who have access to your advisors can see your username and all data about your dominion. It can be turned on by default for packmates in <a href="{{ route('settings') }}">User Settings</a>.</p>
                    <p>If you are no longer able to play, you can <a href="{{ route('dominion.misc.abandon') }}">abandon</a> your dominion.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-crown"></i> The Royal Court</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12 table-responsive">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Role</th>
                                        <th>Dominion</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                @foreach ($governmentHelper->getCourtAppointments() as $appointment)
                                    @php
                                        $appointmentRelation = $selectedDominion->realm->{$appointment['key']};
                                    @endphp
                                    <tr>
                                        <td>
                                            <i class="{{ $appointment['icon'] }} ra-lg text-{{ $appointment['icon-color'] }}"></i>
                                            {{ $appointment['name'] }}
                                        </td>
                                        <td>{{ $appointmentRelation == null ? '--' : $appointmentRelation->name }}</td>
                                        <td>
                                            {{ $appointment['description'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
                    @if ($selectedDominion->isMonarch())
                        <div class="row">
                            <div class="col-md-12">
                                <hr/>
                            </div>
                            <div class="col-md-12">
                                <form action="{{ route('dominion.government.appointments') }}" method="post" role="form">
                                    @csrf
                                    <label for="appointee">Make Appointment</label>
                                    <div class="row">
                                        <div class="col-sm-8">
                                            <div class="form-group">
                                                <select name="appointee" id="appointee" class="form-control select2 dominion_list" style="width: 100%" data-placeholder="Select a dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    <option></option>
                                                    @foreach ($dominions as $dominion)
                                                        @if ($dominion->id !== $selectedDominion->id)
                                                            <option value="{{ $dominion->id }}"
                                                                    data-race="{{ $dominion->race->name }}"
                                                                    data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                                    data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}"
                                                                    data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}">
                                                                {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <select name="role" id="role" class="form-control select2" style="width: 100%" data-placeholder="Select a role" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    <option></option>
                                                    @foreach ($governmentHelper->getCourtAppointments() as $appointment)
                                                        @if ($appointment['key'] !== 'monarch')
                                                            <option value="{{ $appointment['key'] }}">
                                                                {{ $appointment['name'] }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-xs-offset-6 col-xs-6 col-sm-offset-8 col-sm-4 col-lg-offset-10 col-lg-2">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary btn-block" {{ (!$selectedDominion->isMonarch() || $selectedDominion->isLocked()) ? 'disabled' : null }}>
                                                    Submit
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The monarch of a realm may appoint fellow dominions to their royal court, who are then granted access to special perks and responsibilities.</p>
                    <p>The <b>General</b> has the power to cancel and declare wars.</p>
                    <p>The <b>Spymaster</b> can post recurring and black op bounties.</p>
                    <p>The <b>Grand Magister</b> and <b>Court Mage</b> can wield additional spells to protect the realm.</p>
                    <p>The <b>Jester</b> can change the realm name and message.</p>
                    <p>Appointments can only be changed once every five days. Bonuses do not apply during protection.</p>
                </div>
            </div>
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
                                <thead>
                                    <tr>
                                        <th>Realm</th>
                                        <th>Declared By</th>
                                        <th>Declared at</th>
                                        <th>Active at</th>
                                        <th>Inactive at</th>
                                        <th>War Bonus</th>
                                        <th>&nbsp;</th>
                                    </tr>
                                </thead>
                                @foreach ($selectedDominion->realm->warsOutgoing()->active()->get() as $war)
                                    @php
                                        $activeHours = $governmentService->getHoursBeforeWarActive($war);
                                        $cancelHours = $governmentService->getHoursBeforeCancelWar($war);
                                        $endingHours = $governmentService->getHoursBeforeWarEnds($war);
                                        $inactiveHours = $governmentService->getHoursBeforeWarInactive($war);
                                    @endphp
                                    <tr>
                                        <td>{{ $war->targetRealm->name }} (#{{ $war->targetRealm->number }})</td>
                                        <td>#{{ $selectedDominion->realm->number }}</td>
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
                                        <td>
                                            @if ($inactiveHours !== 0)
                                                <span class="small text-muted">Inactive in {{ $inactiveHours }} ticks</span>
                                            @elseif ($activeHours == 0)
                                                @if ($cancelHours !== 0)
                                                    <span class="small text-muted">Cancel in {{ $cancelHours }} ticks</span>
                                                @else
                                                    <span class="small text-muted">Ends in {{ $endingHours }} ticks</span>
                                                @endif
                                            @else
                                                <span class="small text-muted">Active in {{ $activeHours }} ticks</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach ($selectedDominion->realm->warsIncoming()->active()->get() as $war)
                                    @php
                                        $activeHours = $governmentService->getHoursBeforeWarActive($war);
                                        $cancelHours = $governmentService->getHoursBeforeCancelWar($war);
                                        $endingHours = $governmentService->getHoursBeforeWarEnds($war);
                                        $inactiveHours = $governmentService->getHoursBeforeWarInactive($war);
                                    @endphp
                                    <tr>
                                        <td>{{ $war->sourceRealm->name }} (#{{ $war->sourceRealm->number }})</td>
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
                                        <td>
                                            @if ($inactiveHours !== 0)
                                                <span class="small text-muted">Inactive in {{ $inactiveHours }} ticks</span>
                                            @elseif ($activeHours == 0)
                                                @if ($cancelHours !== 0)
                                                    <span class="small text-muted">Cancel in {{ $cancelHours }} ticks</span>
                                                @else
                                                    <span class="small text-muted">Ends in {{ $endingHours }} ticks</span>
                                                @endif
                                            @else
                                                <span class="small text-muted">Active in {{ $activeHours }} ticks</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <div class="col-md-12">
                            @if ($selectedDominion->isMonarch() || $selectedDominion->isGeneral())
                                @if ($governmentService->canDeclareWar($selectedDominion->realm))
                                    <form action="{{ route('dominion.government.war.declare') }}" method="post" role="form">
                                        @csrf
                                        <label for="realm_number">Select a Realm</label>
                                        <div class="row">
                                            <div class="col-sm-8 col-lg-10">
                                                <div class="form-group">
                                                    <select name="realm_number" id="realm_number" class="form-control" required style="width: 100%" data-placeholder="Select a realm" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                        <option></option>
                                                        @if ($selectedDominion->round->start_date <= now())
                                                            @foreach ($realms as $realm)
                                                                @if ($realm->id != $selectedDominion->realm->id)
                                                                    <option value="{{ $realm->number }}">
                                                                        {{ $realm->name }} (#{{ $realm->number }})
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-xs-offset-6 col-xs-6 col-sm-offset-0 col-sm-4 col-lg-2">
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-danger btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                        Declare War
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                                @if ($governmentService->hasDeclaredWar($selectedDominion->realm))
                                    <form action="{{ route('dominion.government.war.cancel') }}" method="post" role="form">
                                        @csrf
                                        <div class="row">
                                            @foreach ($governmentService->getWarsEngaged($selectedDominion->realm->warsOutgoing) as $war)
                                                <div class="col-sm-8 col-lg-10">
                                                    You have declared <span class="text-red text-bold">WAR</span> on {{ $war->targetRealm->name }} (#{{ $war->targetRealm->number }})!
                                                    @if ($governmentService->getHoursBeforeWarActive($war) > 0)
                                                        <br/><small class="text-info">War bonus will be active in {{ $governmentService->getHoursBeforeWarActive($war) }} hours.</small>
                                                    @endif
                                                    @if ($governmentService->getHoursBeforeCancelWar($war) > 0)
                                                        <br/><small class="text-warning">You cannot cancel this war for {{ $governmentService->getHoursBeforeCancelWar($war) }} hours.</small>
                                                    @endif
                                                </div>
                                                <div class="col-xs-offset-6 col-xs-6 col-sm-offset-0 col-sm-4 col-lg-2">
                                                    <button type="submit" class="btn btn-warning btn-block" {{ $selectedDominion->isLocked() || $governmentService->getHoursBeforeCancelWar($war) > 0 ? 'disabled' : null }}>
                                                        Cancel War
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </form>
                                @endif
                            @endif
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
                    <p>Here you can view which realms you currently have war relations with. War cannot be declared until the 4th day of the round. Successful war operations increase your masteries, which increase your ops capabilities.</p>
                    <p>24 hours after war is declared, dominions in both realms have +4% offense as well as +10% land and prestige gains, which remain active for 12 hours after war is cancelled. If both realms have an active war bonus, that increases to +8% offense and +20% land and prestige gains.</p>
                    <p>Additionally, war operations between two dominions at mutual war gain these effects: -20% spy/wizard losses, negative status effects are extended by 18 hours.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-university"></i> Guard Membership</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        @if (!$canJoinGuards)
                            <div class="col-sm-12 text-center">
                                <p class="text-red">You cannot join the Emperor's Royal Guard for the first two days of the round.</p>
                            </div>
                        @endif
                        <div class="col-sm-6 text-center">
                            <h4 class="text-green">
                                <i class="ra ra-heavy-shield" title="Royal Guard"></i>
                                The Emperor's Royal Guard
                            </h4>
                            <ul class="text-left" style="padding: 0 30px;">
                                <li>Cannot interact with Wonders or Dominions less than 60% or greater than 166% of your land size.</li>
                                <li>Hourly platinum production reduced by 2%.</li>
                            </ul>
                            @if ($isRoyalGuardApplicant || $isGuardMember)
                                <form action="{{ route('dominion.government.royal-guard.leave') }}" method="post" role="form" style="padding-bottom: 10px; margin-top: 20px;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $isEliteGuardApplicant || $isEliteGuardMember || $hoursBeforeLeaveRoyalGuard ? 'disabled' : null }}>
                                        @if ($isGuardMember)
                                            Leave Royal Guard
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.royal-guard.join') }}" method="post" role="form" style="padding-bottom: 10px; margin-top: 20px;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                        Request to Join Royal Guard
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="col-sm-6 text-center">
                            <h4 class="text-yellow">
                                <i class="ra ra-heavy-shield" title="Elite Guard"></i>
                                The Emperor's Elite Guard
                            </h4>
                            <ul class="text-left" style="padding: 0 30px;">
                                <li>Cannot interact with Wonders or Dominions less than 75% or greater than 133% of your land size.</li>
                                <li>Hourly platinum production reduced by 2% (from Royal Guard).</li>
                                <li>Exploration platinum cost increased by 25%.</li>
                            </ul>
                            @if ($isEliteGuardApplicant || $isEliteGuardMember)
                                <form action="{{ route('dominion.government.elite-guard.leave') }}" method="post" role="form" style="padding-bottom: 10px; margin-top: 20px;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $hoursBeforeLeaveEliteGuard ? 'disabled' : null }}>
                                        @if ($isEliteGuardMember)
                                            Leave Elite Guard
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.elite-guard.join') }}" method="post" role="form" style="padding-bottom: 10px; margin-top: 20px;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards || !$isRoyalGuardMember ? 'disabled' : null }}>
                                        Request to Join Elite Guard
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="col-sm-12 text-center">
                            <h4 class="text-purple">
                                <i class="ra ra-fire-shield" title="Chaos League"></i>
                                The Chaos League
                            </h4>
                            <ul class="text-left" style="padding: 0 30px;">
                            <li>Enables all war and black operations between members.</li>
                                <li>War spells between members are now CHAOS spells.</li>
                                <ul>
                                    <li>Chaos Fireball - kills 7.5% peasants.</li>
                                    <li>Chaos Lightning - temporarily reduces castle improvements by  0.3%.</li>
                                    <li>Chaos Disband - turns 2% of enemy spies into random resources for yourself.</li>
                                    <li>Chance for critical success, dealing 50% more damage and increasing chance of critical failure.</li>
                                    <li>Chance for critical failure, dealing damage to yourself.</li>
                                    <li>Chance of critical success decreases and chance of critical failure increases based on the number of other members in your realm.</li>
                                </ul>
                                <li>Gain access to self spell: Delve into Shadow (cannot be used in guard).</li>
                                <ul>
                                    <li>Failed CHAOS spells refund 40% of their strength and mana costs.</li>
                                    <li>Reduces exploration cost based on your wizard mastery.</li>
                                </ul>
                                <li>Gain access to friendly spells to use on other members in your realm.</li>
                                <li>75% of casualties suffered due to failed operations between members are automatically re-trained.</li>
                                <li>Info op strength costs are halved (even against non-members).</li>
                            </ul>
                            @if ($isLeavingBlackGuard)
                                <form action="{{ route('dominion.government.black-guard.cancel') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                        Remain in Chaos League
                                    </button>
                                </form>
                            @elseif ($isBlackGuardApplicant || $isBlackGuardMember)
                                <form action="{{ route('dominion.government.black-guard.leave') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $hoursBeforeLeaveBlackGuard ? 'disabled' : null }}>
                                        @if ($isBlackGuardMember)
                                            Leave Chaos League
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.black-guard.join') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                        Request to Join Chaos League
                                    </button>
                                </form>
                            @endif
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
                    <p>Joining the Royal or Elite Guard will reduce the range other dominions can perform hostile interactions against you. In turn, you also can not perform hostile interactions against wonders or dominions outside of your guard range.</p>
                    <p>Upon requesting to join a guard it takes 24 hours for your request to be accepted. If you perform any hostile operations against dominions outside of that guard range, your application is reset back to 24 hours.</p>
                    <p>Once you join a guard, you cannot leave for 2 days. Joining the Royal Guard unlocks the ability to apply for the Elite Guard. You cannot join the guard until the 3rd day of the round.</p>
                    <p>Joining the Chaos League takes 12 hours and you cannot leave for the first 12 hours after joining. Leaving the Chaos League also requires an additional 12 hours to go into effect.</p>

                    @if ($isEliteGuardMember)
                        <p>You are a member of the Emperor's <span class="text-yellow"><i class="ra ra-heavy-shield" title="Elite Guard"></i>Elite Guard</span>.</p>
                        @if ($hoursBeforeLeaveEliteGuard)
                            <p>You cannot leave for {{ $hoursBeforeLeaveEliteGuard }} hours.</p>
                        @endif
                    @elseif ($isRoyalGuardMember)
                        <p>You are a member of the Emperor's <span class="text-green"><i class="ra ra-heavy-shield" title="Royal Guard"></i> Royal Guard</span>.</p>
                        @if ($hoursBeforeLeaveRoyalGuard)
                            <p class="text-red">You cannot leave for {{ $hoursBeforeLeaveRoyalGuard }} hours.</p>
                        @endif
                    @else
                        <p>You are <span class="text-red">NOT</span> a member of the Emperor's Royal or Elite Guard. You cannot interact with dominions less than 40% or greater than 250% of your land size.</p>
                    @endif

                    @if ($isEliteGuardApplicant)
                        <p>You will become a member of the Emperor's Elite Guard in {{ $hoursBeforeEliteGuardMember }} hours.</p>
                    @endif

                    @if ($isRoyalGuardApplicant)
                        <p>You will become a member of the Emperor's Royal Guard in {{ $hoursBeforeRoyalGuardMember }} hours.</p>
                    @endif

                    @if ($isBlackGuardMember)
                        <p>You are a member of the <span class="text-purple"><i class="ra ra-fire-shield" title="Chaos League"></i>Chaos League</span>.</p>
                        @if ($hoursBeforeLeaveBlackGuard)
                            <p class="text-red">You cannot leave for {{ $hoursBeforeLeaveBlackGuard }} hours.</p>
                        @endif
                        @if ($isLeavingBlackGuard)
                            <p>You will leave the Chaos League in {{ $hoursBeforeLeavingBlackGuard }} hours.</p>
                        @endif
                    @elseif ($isBlackGuardApplicant)
                        <p>You will become a member of the Chaos League in {{ $hoursBeforeBlackGuardMember }} hours.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.dominion_list').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#role').select2();
            $('#realm_number').select2();
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const race = state.element.dataset.race;
            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            let difficultyClass;

            if (percentage >= 133) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 60) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            return $(`
                <div class="pull-left">${state.text} - ${race}</div>
                <div class="pull-right">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
