@extends('layouts.master')

@section('page-header', 'Bounty Board')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-hanging-sign"></i> Bounty Board</span>
                </div>
                <div class="card-body table-responsive">
                    @include('partials.dominion.bounty.info-table', [
                        'bounties' => $bountiesActive,
                        'emptyMessage' => 'No bounties available.'
                    ])
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-hanging-sign"></i> Recently Bountied</span>
                </div>
                <div class="card-body table-responsive">
                    @include('partials.dominion.bounty.info-table', [
                        'bounties' => $bountiesInactive,
                        'emptyMessage' => ''
                    ])
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-gem"></i> Realm Valuables Available for Transfer</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    @if ($realmValuablesListed->isEmpty())
                        <p class="text-center text-muted my-3">No valuables are currently listed by your realmies.</p>
                    @else
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Listed By</th>
                                    <th>Valuable</th>
                                    <th>Target</th>
                                    <th>Spy-Hours</th>
                                    <th>Price</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($realmValuablesListed as $valuable)
                                    @php
                                        $config = \OpenDominion\Helpers\ValuablesHelper::getRarityConfig()[$valuable->rarity];
                                        $targetLand = app(\OpenDominion\Calculators\Dominion\LandCalculator::class)->getTotalLand($valuable->targetDominion);
                                        $estimatedSpyHours = (int) ceil($targetLand * $config['spy_hours_multiplier']);
                                        $isOwnListing = $valuable->source_dominion_id === $selectedDominion->id;
                                        $insufficient = $selectedDominion->resource_platinum < $valuable->transfer_price;
                                    @endphp
                                    <tr>
                                        <td>{{ $valuable->sourceDominion->name }}</td>
                                        <td>
                                            <strong>{{ $valuable->name }}</strong><br>
                                            <small class="text-muted">{{ ucfirst($valuable->rarity) }} &middot; {{ ucfirst($valuable->type) }}</small>
                                        </td>
                                        <td>{{ $valuable->targetDominion->name }}</td>
                                        <td>~{{ number_format($estimatedSpyHours) }}</td>
                                        <td>{{ number_format($valuable->transfer_price) }}p</td>
                                        <td class="text-end">
                                            @if ($isOwnListing)
                                                <form action="{{ route('dominion.valuables.unlist', $valuable->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Unlist</button>
                                                </form>
                                            @else
                                                <form action="{{ route('dominion.valuables.purchase', $valuable->id) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" {{ $insufficient ? 'disabled' : '' }} title="{{ $insufficient ? 'Not enough platinum' : '' }}">
                                                        Purchase
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="row">
                <div class="col-sm-12 col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Information</span>
                        </div>
                        <div class="card-body">
                            <p>Info ops that you have requested to be collected by your realmies appear here.</p>
                            <p>The first {{ $bountyService::DAILY_RP_LIMIT }} bounties per day will award {{ $bountyService::REWARD_AMOUNT }} research points.</p>
                            <p>The first {{ $bountyService::DAILY_XP_LIMIT }} bounties per day will award an additional {{ $bountyService::XP_AMOUNT }} XP.</p>
                            <p>Any info op on a dominion that has been marked for observation will count as a bounty. There are currently <b>{{ count($selectedDominion->realm->getSetting('observeDominionIds') ?? []) }}</b> dominions under observation.</p>
                            <p>Bounties collected from bots or ops that have already been taken for the current tick will earn no rewards. You cannot collect your own bounties.</p>
                            <p>You have {{ number_format($selectedDominion->resource_mana) }} mana, {{ sprintf("%.4g", $selectedDominion->wizard_strength) }}% wizard strength, and {{ sprintf("%.4g", $selectedDominion->spy_strength) }}% spy strength.</p>
                            <p>You have collected <b>{{ $bountiesCollected }}</b> bounties today.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
