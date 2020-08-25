@extends('layouts.master')

@section('page-header', 'Re-zone Land')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-refresh"></i> Re-zone Land</h3>
                </div>
                <form action="{{ route('dominion.rezone') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="10%">
                                <col width="10%">
                                <col width="10%">
                                <col width="10%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Land type</th>
                                    <th class="text-center">Barren</th>
                                    <th class="text-center">Amount</th> {{-- wording change? --}}
                                    <th class="text-center">Convert to</th>
                                    <th class="text-center">Amount</th>
                                </tr>
                            </thead>
                            @foreach ($landCalculator->getBarrenLandByLandType($selectedDominion) as $landType => $amount)
                                <tr>
                                    <td>
                                        {{ ucfirst($landType) }}
                                        @if ($landType === $selectedDominion->race->home_land_type)
                                            <br>
                                            <small class="text-muted"><i><span title="This is the land type where your {{ strtolower($selectedDominion->race->name) }} race constructs home buildings on">Home land type</span></i></small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($amount) }}</td>
                                    <td class="text-center">
                                        <input name="remove[{{ $landType }}]" type="number"
                                               class="form-control text-center" placeholder="0" min="0"
                                               max="{{ $amount }}"
                                               value="{{ old('remove.' . $landType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                    <td class="text-center">{{ ucfirst($landType) }}</td>
                                    <td class="text-center">
                                        <input name="add[{{ $landType }}]" type="number"
                                               class="form-control text-center" placeholder="0" min="0"
                                               max="{{ $rezoningCalculator->getMaxAfford($selectedDominion) }}"
                                               value="{{ old('add.' . $landType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Re-Zone</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.land') }}" class="pull-right">Land Advisor</a>
                </div>
                <div class="box-body">
                    <p>Land rezoning is the art of converting land of one type into another type. Land rezoning processes <b>instantly</b>.</p>
                    <p>Each acre of barren land being converted will come at a cost of: {{ $rezoningCalculator->getPlatinumCost($selectedDominion) }} platinum.</p>
                    <p>You have {{ number_format($landCalculator->getTotalBarrenLand($selectedDominion)) }} {{ str_plural('acre', $landCalculator->getTotalBarrenLand($selectedDominion)) }} of barren land and {{ number_format($selectedDominion->resource_platinum) }} platinum.</p>
                    <p>You can afford to re-zone <b>{{ number_format($rezoningCalculator->getMaxAfford($selectedDominion)) }} {{ str_plural('acre', $rezoningCalculator->getMaxAfford($selectedDominion)) }} of barren land</b> at that rate.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
