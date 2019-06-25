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
                        @if ($selectedDominion->isMonarch())
                            <div class="col-md-12">
                                <form action="{{ route('dominion.government.realm') }}" method="post" role="form">
                                    @csrf
                                    <label for="realm_name">Change Realm Name</label>
                                    <div class="row">
                                        <div class="col-sm-8 col-lg-9">
                                            <div class="form-group">
                                                <input class="form-control" name="realm_name" id="realm_name" placeholder="{{ $selectedDominion->realm->name }}" />
                                            </div>
                                        </div>
                                        <div class="col-xs-offset-6 col-xs-6 col-sm-offset-0 col-sm-4 col-lg-2">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    Change
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif
                        <div class="col-md-12">
                            <form action="{{ route('dominion.government.monarch') }}" method="post" role="form">
                                @csrf
                                <label for="monarch">Vote for monarch</label>
                                <div class="row">
                                    <div class="col-sm-8 col-lg-9">
                                        <div class="form-group">
                                            <select name="monarch" id="monarch" class="form-control select2" required style="width: 100%" data-placeholder="Select a dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                <option></option>
                                                @foreach ($selectedDominion->realm->dominions->sortBy('name') as $dominion)
                                                    <option value="{{ $dominion->id }}" data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}" data-networth="{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}">
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
                                <div class="form-group">
                                    <table class="table table-condensed">
                                        <tr><th>Dominion</th><th>Voted for</th></tr>
                                        @foreach ($selectedDominion->realm->dominions->sortBy('name') as $dominion)
                                            <tr>
                                                <td>
                                                    @if ($dominion->isMonarch())
                                                        <span class="text-red">{{ $dominion->name }}</span>
                                                    @else
                                                        {{ $dominion->name }}
                                                    @endif
                                                </td>
                                                @if ($dominion->monarchVote())
                                                    <td>{{ $dominion->monarchVote()->name }}</td>
                                                @else
                                                    <td>N/A</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </table>
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
                    <p>The monarch has the power to declare war and peace as well as moderate the council.</p>
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
                                <p class="text-red">You cannot join the Emperor's Royal Guard for the first five days of the round.</p>
                            </div>
                        @endif
                        <div class="col-sm-6 text-center">
                            <h4 class="text-green">The Emperor's Royal Guard</h4>
                            <ul class="text-left" style="padding: 0px 50px;">
                                <li>Hourly platinum production reduced by 2%</li>
                                <li>Cannot interact with Dominions less than 60% or greater than 166% of your land size.</li>
                            </ul>
                            @if ($isRoyalGuardApplicant || $isRoyalGuardMember)
                                <form action="{{ route('dominion.government.royal-guard.leave') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-danger btn-lg" {{ $selectedDominion->isLocked() || $isEliteGuardApplicant || $isEliteGuardMember ? 'disabled' : null }}>
                                        @if ($isRoyalGuardMember)
                                            Leave Royal Guard
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.royal-guard.join') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                        Join Royal Guard
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="col-sm-6 text-center">
                            <h4 class="text-yellow">The Emperor's Elite Guard</h4>
                            <ul class="text-left" style="padding: 0px 50px;">
                                <li>Exploration cost increased by 25%</li>
                                <li>Cannot interact with Dominions less than 75% or greater than 133% of your land size.</li>
                            </ul>
                            @if ($isEliteGuardApplicant || $isEliteGuardMember)
                                <form action="{{ route('dominion.government.elite-guard.leave') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" name="land" class="btn btn-danger btn-lg" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
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
                                    <button type="submit" name="land" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards || !$isRoyalGuardMember ? 'disabled' : null }}>
                                        Join Elite Guard
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 text-center" style="padding: 20px 50px 0px 50px;">
                            <p>As a member of the Royal or Elite Guard, you must pay a penalty to receive its benefits. Members cannot take action against any Dominion outside of the specified land range. Those Dominions cannot take any action in return either. It takes 24 hours for your application to be accepted. If you attack, cast spells, or send spy operations against Dominions outside of the specified land range your application is reset to 24 hours. Once in the guard, you cannot leave for 2 days. Further, once in the Royal Guard, Dominions can apply for membership in the Elite Guard.</p>
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
                    @if ($isEliteGuardMember)
                        <p>You are a member of the <span class="text-yellow">Emperor's Elite Guard</span>.</p>
                        @if ($hoursBeforeLeaveEliteGuard)
                            <p>You cannot leave for {{ $hoursBeforeLeaveEliteGuard }} hours.</p>
                        @endif
                    @elseif ($isRoyalGuardMember)
                        <p>You are a member of the <span class="text-green">Emperor's Royal Guard</span>.</p>
                        @if ($hoursBeforeLeaveRoyalGuard)
                            <p>You cannot leave for {{ $hoursBeforeLeaveRoyalGuard }} hours.</p>
                        @endif
                    @else
                        <p>You are <span class="text-red">NOT</span> a member of the Emperor's Royal or Elite Guard.</p>
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

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.select2').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const networth = state.element.dataset.networth;

            return $(`
                <div class="pull-left">${state.text}</div>
                <div class="pull-right">${land} land - ${networth} networth</div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
