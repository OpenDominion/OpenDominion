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
                                        <td>{{ $selectedDominion->ruler_name }}</td>
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
                                        <td>{{ number_format($populationCalculator->getEmploymentPercentage($selectedDominion), 2) }}%</td>
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
                                        <td>{{ number_format(floor($selectedDominion->resource_boats + $queueService->getInvasionQueueTotalByResource($selectedDominion, "resource_boats"))) }}</td>
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
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 1)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(1)->name }}:</td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 2)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(2)->name }}:</td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 3)) }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ $selectedDominion->race->units->get(3)->name }}:</td>
                                        <td>{{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, 4)) }}</td>
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

            @if ($dominionProtectionService->isUnderProtection($selectedDominion))
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-shield text-aqua"></i> Under Protection</h3>
                    </div>
                    <div class="box-body">
                        <p>You are under a magical state of protection for <b>{{ number_format($dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> {{ str_plural('hour', $dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion)) }}.</p>
                        <p>During protection you cannot be attacked or attack other dominions. You can neither cast any offensive spells or engage in espionage.</p>
                        <p>You will leave protection on {{ $dominionProtectionService->getProtectionEndDate($selectedDominion)->format('l, jS \o\f F Y \a\t G:i') }}.</p>
                        @if ($dominionProtectionService->getUnderProtectionHoursLeft($selectedDominion) > 71)
                            <p>No production occurs until you have less than 71 hours of protection remaining.</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($selectedDominion->pack !== null)
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Pack</h3>
                    </div>
                    <div class="box-body">
                        <p>You are in pack <em>{{$selectedDominion->pack->name}}</em> with:</p>
                        <ul>
                            @foreach ($selectedDominion->pack->dominions as $dominion)
                                <li>
                                    @if ($dominion->ruler_name === $dominion->name)
                                        <strong>{{ $dominion->name }}</strong>
                                    @else
                                        {{ $dominion->ruler_name }} of <strong>{{ $dominion->name }}</strong>
                                    @endif

                                    @if($dominion->ruler_name !== $dominion->user->display_name)
                                        ({{ $dominion->user->display_name }})
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <p>
                            Slots used: {{ $selectedDominion->pack->dominions->count() }} / {{ $selectedDominion->pack->size }}.
                            @if ($selectedDominion->pack->isFull())
                                (full)
                            @elseif ($selectedDominion->pack->isClosed())
                                (closed)
                            @endif
                        </p>
                        @if (!$selectedDominion->pack->isFull() && !$selectedDominion->pack->isClosed())
                            <p>Your pack will automatically close on <strong>{{ $selectedDominion->pack->getClosingDate() }}</strong> to make space for random players in your realm.</p>
                            @if ($selectedDominion->pack->creator_dominion_id === $selectedDominion->id)
                                <p>
                                    <form action="{{ route('dominion.misc.close-pack') }}" method="post">
                                        @csrf
                                        <button type="submit" class="btn btn-link" style="padding: 0;">Close Pack Now</button>
                                    </form>
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            @endif

            @if (!$selectedDominion->round->hasStarted() && ($selectedDominion->user->last_deleted_dominion_round < $selectedDominion->round->number))
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">Delete</h3>
                    </div>
                    <div class="box-body">
                        <p>You have the ability to delete your dominion <strong>once</strong> this round in case you don't want to play this round after all, you messed up your first hour, or you messed up your pack.</p>
                        <p>This action can only be taken because the round has not yet started. Deleting or restarting your dominion during an active round is <em>not yet</em> possible.</p>
                        <p class="text-red text-bold">Do note that this action is destructive, and your dominion can <u>not</u> be restored by the admins once deleted.</p>
                    </div>
                    <div class="box-footer">
                        <form action="{{ route('dominion.misc.delete-dominion') }}" method="post" onsubmit="return confirm('Are you really sure you want to DELETE your dominion \'{{ $selectedDominion->name }}\' in round {{ $selectedDominion->round->number }}?');">
                            @csrf
                            <button type="submit" class="btn btn-danger">Delete Your Dominion</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection
