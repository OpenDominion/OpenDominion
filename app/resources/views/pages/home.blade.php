@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">
            <div style="margin-bottom: 20px;">
                <img src="{{ asset('assets/app/images/opendominion.png') }}" class="img-responsive" alt="OpenDominion">
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-sm-3">
            <div class="box">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">
                        @if ($currentRound === null)
                            Current Round
                        @else
                            {{ $currentRound->hasStarted() ? 'Current' : 'Next' }} Round: <strong>{{ $currentRound->number }}</strong>
                        @endif
                    </h3>
                </div>
                @if ($currentRound === null || $currentRound->hasEnded())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-red">Inactive</p>
                    </div>
                    <div class="box-body text-center">
                        <p><strong>There is no ongoing round.</strong></p>
                        @if ($discordInviteLink = config('app.discord_invite_link'))
                            <p>Check the Discord for more information.</p>

                            <p style="padding: 0 20px;">
                                <a href="{{ $discordInviteLink }}" target="_blank">
                                    <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive">
                                </a>
                            </p>
                        @endif
                    </div>
                @elseif (!$currentRound->hasStarted() && $currentRound->openForRegistration())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-yellow">Open for Registration</p>
                    </div>
                    <div class="box-body text-center">
                        <p>Registration for round {{ $currentRound->number }} is open.</p>
                        <p>The round starts on {{ $currentRound->start_date }} and lasts for {{ $currentRound->durationInDays() }} days.</p>
                    </div>
                @elseif (!$currentRound->hasStarted())
                    <div class="box-body text-center" style="padding: 0; border-bottom: 1px solid #f4f4f4;">
                        <p style="font-size: 1.5em;" class="text-yellow">Starting Soon</p>
                    </div>
                    <div class="box-body text-center">
                        <p>Registration for round {{ $currentRound->number }} opens on {{ $currentRound->start_date->subDays(3) }}.</p>
                        <p>The round starts on {{ $currentRound->start_date }} and lasts for {{ $currentRound->durationInDays() }} days.</p>
                    </div>
                @else
                    <div class="box-body text-center" style="padding: 0;">
                        <p style="font-size: 1.5em;" class="text-green">Active</p>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="50%">
                                <col width="50%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td class="text-center">Day:</td>
                                    <td class="text-center">
                                        {{ number_format($currentRound->start_date->subDays(1)->diffInDays(now())) }} / {{ number_format($currentRound->durationInDays()) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Players:</td>
                                    <td class="text-center">{{ number_format($currentRound->dominions->count()) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-center">Realms:</td>
                                    <td class="text-center">{{ number_format($currentRound->realms->count()) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer text-center">
                        @if ($currentRound->daysUntilEnd() < 7)
                            <p>
                                <em class="text-red">The round ends in {{ $currentRound->daysUntilEnd() }} {{ str_plural('day', $currentRound->daysUntilEnd()) }}.</em>
                            </p>
                        @else
                            <p>
                                <em>Register to join the ongoing round!</em>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Welcome to OpenDominion!</h3>
                </div>
                <div class="box-body">
                    <p>OpenDominion is a free online text-based strategy game in a medieval fantasy setting. You control a nation called a 'dominion', along with its resources, buildings, land and units. You are placed in a realm with other dominions and you must work together to make your realm the wealthiest and most powerful in the current round!</p>

                    <p>OpenDominion is a free and open source remake of Dominion from Kamikaze Games, which ran from 2000 to 2012 before <a href="https://dominion.opendominion.net/GameOver.htm" target="_blank">stopping indefinitely <i class="fa fa-external-link"></i></a>.</p>

                    <p>To start playing, <a href="{{ route('auth.register') }}">register</a> an account and sign up for a round after registration. If you already have an account, <a href="{{ route('auth.login') }}">login</a> instead.</p>

                    <p>To help you get started, please consult the following resources:</p>

                    <ul>
                        <li><a href="https://opendominion.miraheze.org/wiki/My_First_Round" target="_blank">My First Round <i class="fa fa-external-link"></i></a> on the <a href="https://opendominion.miraheze.org/" target="_blank">OpenDominion Wiki <i class="fa fa-external-link"></i></a>.</li>
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

        <div class="col-sm-3">
            <img src="{{ asset('assets/app/images/elf.png') }}" class="img-responsive" alt="">
        </div>

    </div>
@endsection
