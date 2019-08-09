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
                                <p>Discord is a chat program that I use for OpenDominion's game announcements, its development, and generic banter with other players and people interested in the project.</p>
                                <p>Feel free to join us and chat along!</p>
                            </div>
                        @endif

                        <div class="col-md-4 text-center">
                            <h4>Rate on PBBG.com</h4>
                            <p><a href="https://pbbg.com" target="_blank">PBBG.com</a> is a directory listing of Persistent Browser-Based Games (or PBBG for short), like OpenDominion is!</p>
                            <p>Consider <a href="https://pbbg.com/games/opendominion" target="_blank">rating the project on PBBG.com</a> and share your experience with it, so other people (including potentially new players) know what to expect!</p>
                        </div>

                        @if ($patreonPledgeLink = config('app.patreon_pledge_link'))
                            <div class="col-md-4 text-center">
                                <h4>Become a Patron</h4>
                                <p>
                                    <a href="{{ $patreonPledgeLink }}" data-patreon-widget-type="become-patron-button">Become a Patron!</a>
                                </p>
                                <p>OpenDominion is (and always will be) fully free to play, with no advertisements, micro-transactions, lootboxes, premium currencies, or paid DLCs.</p>
                                <p>I've put in a lot of effort into OpenDominion over the past six years, and I've been paying everything I needed to help me build and run OD out of my own pocket. Financial support through Patreon (even a single dollar) is therefore most welcome!</p>
                                <p>(Because of my strict 'no-P2W'-policy, no in-game benefits will be given to donators over regular players. You will get a spiffy color in the Discord, though!)</p>
                            </div>
                        @endif
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
                    <p>The Platinum Bonus instantly gives you {{ number_format($selectedDominion->peasants * 4) }} platinum.</p>
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
