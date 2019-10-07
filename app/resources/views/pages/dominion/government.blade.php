@extends('layouts.master')

@section('page-header', 'Government')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-university"></i> Guard Membership</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        @if (!$canJoinGuards)
                            <div class="col-xs-12 text-center">
                                <p class="text-red">You cannot join the Emperor's Royal Guard for the first five days of the round.</p>
                            </div>
                        @endif
                        <div class="col-sm-6 text-center">
                            <h4 class="text-green">
                                <i class="ra ra-heavy-shield" title="Royal Guard"></i>
                                The Emperor's Royal Guard
                            </h4>
                            <ul class="text-left" style="padding: 0 50px;">
                                <li>Cannot interact with Dominions less than 60% or greater than 166% of your land size.</li>
                                <li>Hourly platinum production reduced by 2%.</li>
                            </ul>
                            @if ($isRoyalGuardApplicant || $isGuardMember)
                                <form action="{{ route('dominion.government.royal-guard.leave') }}" method="post" role="form" style="padding-bottom: 10px;">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $isEliteGuardApplicant || $isEliteGuardMember || $hoursBeforeLeaveRoyalGuard ? 'disabled' : null }}>
                                        @if ($isGuardMember)
                                            Leave Royal Guard
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.royal-guard.join') }}" method="post" role="form" style="padding-bottom: 10px;">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
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
                            <ul class="text-left" style="padding: 0 50px;">
                                <li>Cannot interact with Dominions less than 75% or greater than 133% of your land size.</li>
                                <li>Hourly platinum production reduced by 2%.</li>
                                <li>Exploration platinum cost increased by 25%.</li>
                            </ul>
                            @if ($isEliteGuardApplicant || $isEliteGuardMember)
                                <form action="{{ route('dominion.government.elite-guard.leave') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $hoursBeforeLeaveEliteGuard ? 'disabled' : null }}>
                                        @if ($isEliteGuardMember)
                                            Leave Elite Guard
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.elite-guard.join') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards || !$isRoyalGuardMember ? 'disabled' : null }}>
                                        Request to Join Elite Guard
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
                    <p>Joining a guard will reduce the range other dominions can perform hostile interactions against you. In turn, you also can not perform hostile interactions against dominions outside of your guard range.</p>
                    <p>Upon requesting to join a guard it takes 24 hours for your request to be accepted. If you perform any hostile operations against dominions outside of that guard range, your application is reset back to 24 hours.</p>
                    <p>Once you join a guard, you cannot leave for 2 days. Joining the Royal Guard unlocks the ability to apply for the Elite Guard.</p>

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
                </div>
            </div>
        </div>
    </div>
@endsection
