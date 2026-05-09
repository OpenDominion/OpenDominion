@extends('layouts.topnav')

@section('content')
    <div class="row">

        <div class="col-md-6">

            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title">Welcome to OpenDominion!</span>
                </div>
                <div class="card-body">
                    <p>OpenDominion is a text-based multiplayer strategy game in a medieval fantasy world. You rule a dominion &mdash; its land, resources, buildings, and military &mdash; alongside other players in an allied realm. Expand, conquer, and wage war on your way to making your realm the wealthiest and most powerful in the land.</p>

                    <p>OpenDominion runs in real time. Every hour, the game ticks forward: resources accrue, construction completes, and military orders carry out around the clock — so a round unfolds gradually over several weeks, with or without you at the keyboard.<p>

                    <p>OpenDominion is a free and open source remake of Dominion from Kamikaze Games, which ran from 2000 to 2012 before <a href="http://dominion.opendominion.net/GameOver.htm" target="_blank">shutting down <i class="fa fa-external-link"></i></a>.</p>

                    <figure class="figure d-block text-center mt-3 mb-0">
                        <img src="{{ asset('assets/app/images/classic.png') }}" class="figure-img img-fluid rounded border" alt="Screenshot of the original Dominion game">
                        <figcaption class="figure-caption">A screenshot of the original Dominion (Kamikaze Games, 2000&ndash;2012).</figcaption>
                    </figure>
                </div>
            </div>

        </div>

        <div class="col-md-6">

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Getting Started</span>
                </div>
                <div class="card-body">
                    <p>To help you get started, please consult the following resources:</p>

                    <ul>
                        <li><a href="{{ route('scribes.overview') }}">How to Play (The Scribes)</a></li>
                        <li><a href="https://wiki.opendominion.net/wiki/My_First_Round" target="_blank">My First Round <i class="fa fa-external-link"></i></a> on the <a href="https://wiki.opendominion.net/" target="_blank">OpenDominion Wiki <i class="fa fa-external-link"></i></a>.</li>
                        <li>A mirror of the <a href="http://dominion.opendominion.net/" target="_blank">original website <i class="fa fa-external-link"></i></a> <strong>(Outdated)</strong></li>
                    </ul>

                    @if ($discordInviteLink = config('app.discord_invite_link'))
                        <p>Join the <a href="{{ $discordInviteLink }}" target="_blank">Discord server <i class="fa fa-external-link"></i></a> for game announcements and game-related chat.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Vision Statement</span>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Always free, always open source.</li>
                        <li>Remain true to the spirit of the original game while improving player experience.</li>
                        <li>Introduce new content to keep each round exciting.</li>
                        <li>Support and encourage a variety of playstyles.</li>
                        <li>Grow the playerbase.</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Game Balance</span>
                </div>
                <div class="card-body">
                    <p>The Gameplay Committee is a group of player volunteers who make informed and thoughtful decisions about game balance, which can then be implemented at the administrators' discretion. Members are tasked with communicating to the playerbase what changes have been proposed, gathering feedback, and finalizing changes for each round based on relative consensus within the community.</p>

                    <p>The primary objective of the game is to gain land. This can be done by attacking, exploring, or a combination of the two (converting).</p>

                    <p>Balance changes should meet at least one of these criteria:</p>
                    <ul>
                        <li>Better balances the aforementioned three basic playstyles, so that each is viable to win a round (though individuals may disagree about the likelihood of each).</li>
                        <li>Adjusts the power level of individual races/mechanics/strategies to maximize strategic diversity.</li>
                        <li>Discourages abusive behavior that would violate the rules or harm player retention.</li>
                        <li>Keeps the game fresh for existing players by shaking things up on occasion.</li>
                        <li>Reduces the learning curve for new players.</li>
                    </ul>

                    <p>Proposals for a change can be presented by any player. A proposal should, at minimum, clearly explain the reasoning for the change. If possible, it should also show how it affects other aspects of the game and/or present charts or other visual reference so that the outcome can be more easily understood. Relevant game data from previous rounds can be requested from the admins. Proposals should be presented well in advance of their expected implementation.</p>
                </div>
            </div>

        </div>

    </div>
@endsection
