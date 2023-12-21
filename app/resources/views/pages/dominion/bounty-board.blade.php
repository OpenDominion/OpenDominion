@extends('layouts.master')

@section('page-header', 'Bounty Board')

@php
    //$infoSpells = $spellHelper->getSpells($dominion->race, 'info');
@endphp

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
                                        $bountyTypes = $dominionBounties->pluck('source_dominion_id', 'type');
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
                                            @if ($bountyTypes->has('clear_sight'))
                                                <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="spell" value="clear_sight">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Cast Spell"
                                                        {{ ($bountyTypes->get('clear_sight') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'clear_sight']) }}" data-toggle="tooltip" title="Request a Clear Sight">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('revelation'))
                                                <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="spell" value="revelation">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Cast Spell"
                                                        {{ ($bountyTypes->get('revelation') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'revelation']) }}" data-toggle="tooltip" title="Request a Revelation">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('castle_spy'))
                                                <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="operation" value="castle_spy">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Perform Operation"
                                                        {{ ($bountyTypes->get('castle_spy') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'castle_spy']) }}" data-toggle="tooltip" title="Request a Castle Spy">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('barracks_spy'))
                                                <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="operation" value="barracks_spy">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Perform Operation"
                                                        {{ ($bountyTypes->get('barracks_spy') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'barracks_spy']) }}" data-toggle="tooltip" title="Request a Barracks Spy">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('survey_dominion'))
                                                <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="operation" value="survey_dominion">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Perform Operation"
                                                        {{ ($bountyTypes->get('survey_dominion') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'survey_dominion']) }}" data-toggle="tooltip" title="Request a Survey Dominion">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('land_spy'))
                                                <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="operation" value="land_spy">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Perform Operation"
                                                        {{ ($bountyTypes->get('land_spy') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'land_spy']) }}" data-toggle="tooltip" title="Request a Land Spy">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('vision'))
                                                <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="spell" value="vision">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Cast Spell"
                                                        {{ ($bountyTypes->get('vision') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'vision']) }}" data-toggle="tooltip" title="Request a Vision">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($bountyTypes->has('disclosure'))
                                                <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                                    @csrf
                                                    <input type="hidden" name="target_dominion" value="{{ $targetDominion->id }}">
                                                    <input type="hidden" name="spell" value="disclosure">
                                                    <button type="submit" class="btn btn-xs btn-primary" data-toggle="tooltip" title="Cast Spell"
                                                        {{ ($bountyTypes->get('disclosure') == $selectedDominion->id) ? 'disabled' : null }}
                                                    >
                                                        <i class="fa fa-star"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <a href="{{ route('dominion.bounty-board.create', [$targetDominion->id, 'disclosure']) }}" data-toggle="tooltip" title="Request a Disclosure">
                                                    <i class="fa fa-star-o" style="margin-top: 4px;"></i>
                                                </a>
                                            @endif
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
                            <p>Each bounty collected will award 4 XP and 20 research points. You can collect up to 24 bounties per day and you cannot collect your own bounties.</p>
                            <p>You have collected <b>{{ $bountiesCollected }}</b> bounties today.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
