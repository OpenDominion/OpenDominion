@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">

            <div style="margin-bottom: 20px;">
                <img src="{{ asset('assets/app/images/opendominion.png') }}" class="img-responsive" alt="OpenDominion">
            </div>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Welcome to OpenDominion!</h3>
                </div>
                <div class="box-body">
                    <p>OpenDominion is a free online text-based strategy game in a medieval fantasy setting. You control a nation called a 'dominion', along with its resources, buildings, land and units. You are placed in a realm with up to 11 other dominions and you must work together to make your realm the wealthiest and most powerful in the current round!</p>

                    <p>OpenDominion is a free and open source remake of Dominion from Kamikaze Games, which ran from 2000 to 2012 before <a href="https://dominion.opendominion.net/GameOver.htm" target="_blank">stopping indefinitely <i class="fa fa-external-link"></i></a>.</p>

                    <p>To start playing, <a href="{{ route('auth.register') }}">register</a> an account and sign up for a round after registration. If you already have an account, <a href="{{ route('auth.login') }}">login</a> instead.</p>

                    <p>To help you get started, please consult the following resources:</p>

                    <ul>
                        <li>A mirror of Dominion's manual: <a href="https://dominion.opendominion.net/scribes.html" target="_blank">The Scribes <i class="fa fa-external-link"></i></a></li>
                        <li><a href="http://web.archive.org/web/20131226013425/http://dominion.lykanthropos.com:80/wiki/index.php/The_Complete_Newbie_Guide" target="_blank">The Complete Newbie Guide <i class="fa fa-external-link"></i></a> on the Web Archive</li>
                    </ul>

                    <p>Do note that OpenDominion is still in development and not all features from Dominion are present in OpenDominion.</p>

                    @if ($discordInviteLink = config('app.discord_invite_link'))
                        <p>Also feel free to join the OpenDominion <a href="{{ $discordInviteLink }}" target="_blank">Discord server <i class="fa fa-external-link"></i></a>! It's the main place for game announcements, game-related chat and development chat.</p>
                    @endif

                    <p>OpenDominion is open source software and can be found on <a href="https://github.com/WaveHack/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>.</p>
                </div>
            </div>

        </div>
    </div>
@endsection
