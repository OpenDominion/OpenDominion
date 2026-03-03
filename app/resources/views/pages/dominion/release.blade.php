@extends('layouts.master')

@section('page-header', 'Release Troops')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card border-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="ra ra-cycle"></i> Release Troops</h3>
                </div>
                <form action="{{ route('dominion.military.release') }}" method="post" role="form">
                    @csrf
                    <div class="card-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="10%">
                                <col width="15%">
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
                                        {!! $unitHelper->getUnitTypeIconHtml('draftees', $selectedDominion->race) !!}
                                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $unitHelper->getUnitHelpString('draftees', $selectedDominion->race) }}">
                                            Draftees
                                        </span>
                                    </td>
                                    <td class="text-center">{{ number_format($selectedDominion->military_draftees) }}</td>
                                    <td class="text-center">
                                        <div class="input-group">
                                            <input type="number" name="release[draftees]" class="form-control text-center" placeholder="0" min="0" max="{{ $selectedDominion->military_draftees }}" value="{{ old('release.draftees') }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            <span class="input-group-btn">
                                                <button class="btn btn-danger release-max" type="button" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    Max
                                                </button>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($unitHelper->getUnitTypes() as $unitType)
                                    <tr>
                                        <td>
                                            {!! $unitHelper->getUnitTypeIconHtml($unitType, $selectedDominion->race) !!}
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race) }}">
                                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            </span>
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
                    <div class="card-footer">
                        <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Release</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Information</h3>
                </div>
                <div class="card-body">
                    <p><b>Warning</b>: You are about to release your troops.</p>
                    <p>Draftees will release into the peasantry. Other troops into draftees.</p>
                    <p>Any resources (including spies and wizards) used to train any released unit <b>will be lost</b>.</p>
                    <p>Releasing troops processes <b>instantly</b>.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.release-max').click(function(e) {
                var drafteeInput = $('input[name=release\\[draftees\\]]');
                var maxAmount = drafteeInput.attr('max');

                drafteeInput.val(maxAmount);
            });
        })(jQuery);
    </script>
@endpush
