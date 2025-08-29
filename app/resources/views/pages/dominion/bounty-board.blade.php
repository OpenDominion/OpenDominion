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
                    @include('partials.dominion.bounty.info-table', [
                        'bounties' => $bountiesActive,
                        'emptyMessage' => 'No bounties available.'
                    ])
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title"><i class="ra ra-hanging-sign"></i> Recently Bountied</h3>
                </div>
                <div class="box-body table-responsive">
                    @include('partials.dominion.bounty.info-table', [
                        'bounties' => $bountiesInactive,
                        'emptyMessage' => ''
                    ])
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
                            <p>The first {{ $bountyService::DAILY_RP_LIMIT }} bounties per day will award {{ $bountyService::REWARD_AMOUNT }} research points.</p>
                            <p>The first {{ $bountyService::DAILY_XP_LIMIT }} bounties per day will award {{ $bountyService::XP_AMOUNT }} XP.</p>
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
