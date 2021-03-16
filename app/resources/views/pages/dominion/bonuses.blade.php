@extends('layouts.master')

@section('page-header', 'Daily Bonus')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-plus"></i> Daily bonuses</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6 text-center">
                            <form action="{{ route('dominion.bonuses.land') }}" method="post" role="form">
                                @csrf
                                <button type="submit" name="land" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || $selectedDominion->daily_land ? 'disabled' : null }}>
                                    <i class="ra ra-honeycomb ra-lg"></i>
                                    Land Bonus
                                </button>
                            </form>
                        </div>
                        <div class="col-xs-6 text-center">
                            <form action="{{ route('dominion.bonuses.platinum') }}" method="post" role="form">
                                @csrf
                                <button type="submit" name="platinum" class="btn btn-primary btn-lg" {{ $selectedDominion->isLocked() || $selectedDominion->daily_platinum ? 'disabled' : null }}>
                                    <i class="ra ra-gold-bar ra-lg"></i>
                                    Platinum Bonus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-body">
                    <p>While you're here, consider supporting the project in one (or more) of the following ways:</p>

                    <div class="row">
                        @if ($discordInviteLink = config('app.discord_invite_link'))
                            <div class="col-md-4 text-center">
                                <h4>Join the chat</h4>
                                <p>
                                    <a href="{{ $discordInviteLink }}" target="_blank">
                                        <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive" style="max-width: 200px; margin: 0 auto;">
                                    </a>
                                </p>
                                <p>Discord is a real-time communications platform and the best place to follow OpenDominion's announcements, development, gameplay discussions, and generic banter with other players.</p>
                                <p>Feel free to join us!</p>
                            </div>
                        @endif

                        <div class="col-md-4 text-center">
                            <h4>Rate on PBBG.com</h4>
                            <p><a href="https://pbbg.com" target="_blank">PBBG.com</a> is a directory listing of Persistent Browser-Based Games (or PBBG for short), like OpenDominion is!</p>
                            <p>Consider <a href="https://pbbg.com/games/opendominion" target="_blank">rating the project on PBBG.com</a> and share your experience with it, so other people (including potentially new players) know what to expect!</p>
                        </div>

                        <div class="col-md-4 text-center">
                            <h4>Become a Patron</h4>
                            @if ($patreonPledgeLink = config('app.patreon_pledge_link'))
                                <p><a href="{{ $patreonPledgeLink }}" data-patreon-widget-type="become-patron-button">Become a Patron!</a></p>
                            @elseif ($kofiSupportID = config('app.kofi_support_id'))
                                <p><script type='text/javascript' src='https://ko-fi.com/widgets/widget_2.js'></script><script type='text/javascript'>kofiwidget2.init('Support OpenDominion', '#005566', '{{ $kofiSupportID }}');kofiwidget2.draw();</script></p>
                            @endif
                            <p>
                                OpenDominion is (and always will be) fully free to play; with no advertisements, micro-transactions, lootboxes, premium currencies, or paid DLCs.
                                Financial support is therefore most welcome! (No in-game benefits will be given to donors over regular players. You will get a spiffy color in the Discord, though!)
                            </p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <p>Thank you for your attention, and please enjoy playing OpenDominion!</p>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The Platinum Bonus instantly gives you 4 platinum per peasant (currently {{ number_format($selectedDominion->peasants * 4) }}) and 750 research points.</p>
                    <p>The Land Bonus instantly gives you 20 acres of {{ str_plural($selectedDominion->race->home_land_type) }}.</p>
                    <p>Both bonuses can be claimed once per day.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@if (config('app.patreon_pledge_link'))
    @push('page-scripts')
        <script async src="https://c6.patreon.com/becomePatronButton.bundle.js"></script>
    @endpush

    @push('inline-styles')
        <style type="text/css">
            .patreon-widget {
                width: 176px !important;
            }
        </style>
    @endpush
@endif
