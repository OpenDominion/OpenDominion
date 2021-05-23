@extends('layouts.master')

@section('page-header', 'Status')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-player-pain"></i> Abandon Dominion</h3>
                </div>
                <div class="box-body">
                    <p>You can request that your dominion be abandoned at any time.</p>
                    <p>Abandonment requires a 24 hour wait to take effect. During this time period you cannot perform hostile magic/espionage operations or invasions. Doing so will reset the wait period to 24 hours. Additionally, 12 hours will be added if you become the victim of an invasion.</p>
                </div>
                <form id="abandon-dominion" class="form" action="{{ route('dominion.misc.abandon') }}" method="post">
                    @csrf
                    <div class="box-footer">
                        <button type="submit" class="btn btn-danger" {{ $selectedDominion->abandoned_at !== null ? "disabled" : null }}>Abandon</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-angel-wings"></i> Cancel Abandon Dominion</h3>
                </div>
                <div class="box-body">
                    <p>You can cancel your request at any time during the wait period.</p>
                    @if ($selectedDominion->abandoned_at !== null)
                        <p class="text-danger">
                            You have chosen to abandon your dominion.
                            @if ($selectedDominion->abandoned_at > now())
                                It will be locked in {{ $selectedDominion->abandoned_at->longAbsoluteDiffForHumans(now()) }}.</p>
                            @endif
                        </p>
                    @endif
                </div>
                <form id="cancel-abandon-dominion" class="form" action="{{ route('dominion.misc.abandon.cancel') }}" method="post">
                    @csrf
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->abandoned_at == null ? "disabled" : null }}>Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
