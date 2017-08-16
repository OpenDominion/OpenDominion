@extends('layouts.master')

@section('page-header', 'Destroy Buildings')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-demolish"></i> Destroy Buildings</h3>
                </div>
                <form action="{{ route('dominion.destroy') }}" method="post" role="form">
                    {!! csrf_field() !!}
                    <div class="box-body no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                            </colgroup>

                            @foreach ($buildingHelper->getBuildingTypesByRace($selectedDominion->race) as $landType => $buildingTypes)

                                @if (empty($buildingTypes))
                                    @continue
                                @endif

                                <thead>
                                    <tr>
                                        <th colspan="3">{{ ucfirst($landType) }} <span class="small">(Barren: {{ number_format($landCalculator->getTotalBarrenLandByLandType($selectedDominion, $landType)) }})</span></th>
                                    </tr>
                                    <tr>
                                        <th>Building</th>
                                        <th class="text-center">Owned</th>
                                        <th class="text-center">Destroy</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($buildingTypes as $buildingType)
                                        <tr>
                                            <td>
                                                {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                                {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}<br>
                                                <span class="text-muted"><i>{{ $buildingHelper->getBuildingHelpString($buildingType) }}</i></span>
                                            </td>
                                            <td class="text-center">
                                                {{ $selectedDominion->{'building_' . $buildingType} }}
                                                <small>
                                                    ({{ number_format((($selectedDominion->{'building_' . $buildingType} / $landCalculator->getTotalLand($selectedDominion)) * 100), 1) }}%)
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="destroy[{{ $buildingType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->{'building_' . $buildingType} }}" value="{{ old('destroy.' . $buildingType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>

                            @endforeach

                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Destroy</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p><b>Warning</b>: You are about to destroy buildings.</p>
                    <p>The platinum and lumber used to construct any destroyed buildings <b>will be lost</b>. No reimbursements other than the barren land used to build it upon will be issued.</p>
                    <p>Destroying buildings processes <b>instantly</b>.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
