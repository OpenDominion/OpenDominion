@extends('layouts.master')

@section('page-header', 'Release Troops')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-cycle"></i> Release Troops</h3>
                </div>
                <form action="{{ route('dominion.military.release') }}" method="post" role="form">
                    {!! csrf_field() !!}
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="100">
                                <col width="100">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th class="text-center">Owned</th>
                                    <th class="text-center">Release</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        Draftees
                                        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $selectedDominion->race) }}"></i>
                                    </td>
                                    <td class="text-center">{{ number_format($selectedDominion->military_draftees) }}</td>
                                    <td class="text-center">
                                        <input type="number" name="release[draftees]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->military_draftees }}" value="{{ old('release.draftees') }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                    </td>
                                </tr>
                                @foreach ($unitHelper->getUnitTypes() as $unitType)
                                    <tr>
                                        <td>
                                            {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race) }}"></i>
                                        </td>
                                        <td class="text-center">{{ number_format($selectedDominion->{'military_' . $unitType}) }}</td>
                                        <td class="text-center">
                                            <input type="number" name="release[{{ $unitType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->{'military_' . $unitType} }}" value="{{ old('release.' . $unitType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Release</button>
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
                    <p><b>Warning</b>: You are about to release your troops.</p>
                    <p>Draftees will release into the peasantry. Other troops into draftees.</p>
                    <p>Any platinum, ore and wizards used to train any released unit <b>will be lost</b>.</p>
                    <p>Releasing troops processes <b>instantly</b>.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
