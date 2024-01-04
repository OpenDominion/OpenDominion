@extends('layouts.master')

@section('page-header', 'Bounty Board')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><i class="ra ra-hanging-sign"></i> Bounty Board</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th class="text-center">Race</th>
                                <th class="text-center">Land</th>
                                <th class="text-center">Range</th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Clear Sight (Magic)">CS</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Revelation (Magic)">Rev</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Castle Spy (Espionage)">Cas</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Barracks Spy (Espionage)">BS</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Survey Dominion (Espionage)">Sur</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Land Spy (Espionage)">Lan</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Vision (Magic)">Vis</span>
                                </th>
                                <th class="text-center">
                                    <span data-toggle="tooltip" title="Disclosure (Magic)">Dis</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($bounties->isEmpty())
                                <tr>
                                    <td colspan="12" class="text-center">No bounties available.</td>
                                </tr>
                            @else
                                @foreach ($bounties as $dominionBounties)
                                    @php
                                        $targetDominion = $dominionBounties->first()->targetDominion;
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('dominion.op-center.show', $targetDominion->id) }}">
                                                {{ $targetDominion->name }} (#{{ $targetDominion->realm->number }})
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            {{ $targetDominion->race->name }}
                                        </td>
                                        <td class="text-center">
                                            {{ $landCalculator->getTotalLand($targetDominion) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="small {{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $targetDominion) }}">
                                                {{ number_format($rangeCalculator->getDominionRange($selectedDominion, $targetDominion), 2) }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'magic',
                                                'opType' => 'clear_sight',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('clear_sight')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'magic',
                                                'opType' => 'revelation',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('revelation')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'espionage',
                                                'opType' => 'castle_spy',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('castle_spy')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'espionage',
                                                'opType' => 'barracks_spy',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('barracks_spy')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'espionage',
                                                'opType' => 'survey_dominion',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('survey_dominion')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'espionage',
                                                'opType' => 'land_spy',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('land_spy')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'magic',
                                                'opType' => 'vision',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('vision')
                                            ])
                                        </td>
                                        <td class="text-center">
                                            @include('partials.dominion.bounty.board-item', [
                                                'type' => 'magic',
                                                'opType' => 'disclosure',
                                                'selectedDominion' => $selectedDominion,
                                                'targetDominion' => $targetDominion,
                                                'bounty' => $dominionBounties->get('disclosure')
                                            ])
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Information</h3>
                        </div>
                        <div class="box-body">
                            <p>Info ops that you have requested to be collected by your realmies appear here.</p>
                            <p>Each bounty collected will award double XP and the first {{ $bountyService::DAILY_LIMIT }} bounties per day will award {{ $bountyService::REWARD_AMOUNT }} research points.</p>
                            <p>Bounties collected from bots or ops that have already been taken for the current tick will earn no rewards. You cannot collect your own bounties.</p>
                            <p>You have {{ number_format($selectedDominion->resource_mana) }} mana, {{ sprintf("%.4g", $selectedDominion->wizard_strength) }}% wizard strength, and {{ sprintf("%.4g", $selectedDominion->spy_strength) }}% spy strength.</p>
                            <p>You have collected <b>{{ $bountiesCollected }}</b> rewards from bounties today.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
