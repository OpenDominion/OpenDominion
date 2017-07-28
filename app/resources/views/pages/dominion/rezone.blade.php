@extends('layouts.master')

@section('page-header', 'Construction')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-cycle"></i> Re-zone land</h3>
                </div>
                <form action="{{ route('dominion.rezone') }}" method="post">
                    {!! csrf_field() !!}
                    <div class="box-body no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                                <col width="100">
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Terrain</th>
                                    <th>Available</th>
                                    <th>Amount</th>
                                    <th>Convert to</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            @foreach ($barrenLand as $landType => $amount)
                                <tr>
                                    <td>{{ ucfirst($landType) }}</td>
                                    <td>{{ number_format($amount) }}</td>
                                    <td>
                                        <input name="remove[{{ $landType }}]" type="number"
                                               class="form-control text-center" placeholder="0" min="0"
                                               max="{{ $amount }}"
                                               value="{{ old('remove.' . $landType) }}" {{ $dominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                    <td>{{ ucfirst($landType) }}:</td>
                                    <td>
                                        <input name="add[{{ $landType }}]" type="number"
                                               class="form-control text-center" placeholder="0" min="0"
                                               max="{{ $canAfford }}"
                                               value="{{ old('add.' . $landType) }}" {{ $dominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                </tr>

                            @endforeach

                        </table>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" {{ $dominion->isLocked() ? 'disabled' : null }}>Re-Zone</button>
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
                    <p>Each acre of land being converted will come at a cost of: {{ $rezoningPlatinumCost }}
                        platinum.</p>
                    <p>You can afford to re-zone: {{ number_format($canAfford) }} acres.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
