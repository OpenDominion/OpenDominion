@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> The Dominion of {{ $selectedDominion->name }}</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="row">

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Overview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Ruler:</td>
                                        <td>{{ Auth::user()->display_name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Race:</td>
                                        <td>{{ $selectedDominion->race->name }}</td>
                                    </tr>
                                    <tr>
                                        <td>Land:</td>
                                        <td>{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Peasants:</td>
                                        <td>{{ number_format($selectedDominion->peasants) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Employment:</td>
                                        <td>{{ number_format($populationCalculator->getEmploymentPercentage($selectedDominion)) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Networth:</td>
                                        <td>{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Prestige:</td>
                                        <td>{{ number_format($selectedDominion->prestige) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Resources</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Platinum:</td>
                                        <td>{{ number_format($selectedDominion->resource_platinum) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Food:</td>
                                        <td>{{ number_format($selectedDominion->resource_food) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Lumber:</td>
                                        <td>{{ number_format($selectedDominion->resource_lumber) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Mana:</td>
                                        <td>{{ number_format($selectedDominion->resource_mana) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Ore:</td>
                                        <td>{{ number_format($selectedDominion->resource_ore) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Gems:</td>
                                        <td>{{ number_format($selectedDominion->resource_gems) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="nyi">Research Points:</td>
                                        <td class="nyi">{{ number_format($selectedDominion->resource_tech) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Boats:</td>
                                        <td>{{ number_format(floor($selectedDominion->resource_boats)) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-xs-12 col-sm-4">
                            <table class="table">
                                <colgroup>
                                    <col width="50%">
                                    <col width="50%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th colspan="2">Military</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Morale:</td>
                                        <td>{{ number_format($selectedDominion->morale) }}%</td>
                                    </tr>
                                    <tr>
                                        <td>Draftees:</td>
                                        <td>{{ number_format($selectedDominion->military_draftees) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(0)->name }}:</td>
                                        <td>{{ number_format($selectedDominion->military_unit1) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(1)->name }}:</td>
                                        <td>{{ number_format($selectedDominion->military_unit2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(2)->name }}:</td>
                                        <td>{{ number_format($selectedDominion->military_unit3) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(3)->name }}:</td>
                                        <td>{{ number_format($selectedDominion->military_unit4) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Spies:</td>
                                        <td>{{ number_format($selectedDominion->military_spies) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Wizards:</td>
                                        <td>{{ number_format($selectedDominion->military_wizards) }}</td>
                                    </tr>
                                    <tr>
                                        <td>ArchMages:</td>
                                        <td>{{ number_format($selectedDominion->military_archmages) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            @if ($discordInviteLink = config('app.discord_invite_link'))
                <div style="margin-bottom: 20px;">
                    <a href="{{ $discordInviteLink }}" target="_blank">
                        <img src="{{ asset('assets/app/images/join-the-discord.png') }}" alt="Join the Discord" class="img-responsive">
                    </a>
                </div>
            @endif

            @if ($dominionProtectionService->isUnderProtection($selectedDominion))
                <div class="box box-warning">
                    <div class="box-body">
                        <p>You are under a magical state of protection for <b>{{ number_format($dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> {{ str_plural('hour', $dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion)) }}.</p>
                        <p>During protection you cannot be attacked or attack other dominions. You can neither cast any offensive spells or engage in espionage.</p>
                        {{-- todo: remove line below once those things have been developed --}}
                        <p><i>You can't do that regardless yet because OpenDominion is still in development and those features haven't been built yet.</i></p>
                        <p>You will leave protection at {{ $dominionProtectionService->getProtectionEndDate($selectedDominion)->format(DATE_RFC2822) }}.</p>
                        @if ($dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion) > 71)
                            <p>No production occurs until you have less than 71 hours of protection remaining.</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- todo: message about black ops not being enabled until 8th day in the round --}}

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This section gives you a quick overview of your dominion.</p>
                    <p>Your total land size is {{ number_format($landCalculator->getTotalLand($selectedDominion)) }} and networth is {{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}.</p>
                    <p><a href="{{ route('dominion.rankings', 'land') }}">My Rankings</a></p>
                </div>
            </div>
        </div>

    </div>
@endsection
