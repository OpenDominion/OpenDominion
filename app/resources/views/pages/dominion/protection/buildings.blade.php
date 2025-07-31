@extends('layouts.master')

@section('page-header', 'Starting Buildings')

@section('content')
    <div class="row">
        @php
            $defaultBuildings = $buildingHelper->getDefaultBuildings($selectedDominion->protection_type == 'quick');
        @endphp
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-home"></i> Select Starting Buildings</h3>
                </div>
                <form action="{{ route('dominion.protection.buildings') }}" method="post" role="form">
                    @csrf
                    <div class="box-body">
                        <div class="row">

                            <div class="col-md-12 col-lg-6">
                                <table class="table table-condensed" style="margin-bottom: 0px;">
                                    <colgroup>
                                        <col width="50%">
                                        <col width="50%">
                                    </colgroup>
                                    @foreach ($buildingsByLandType->only(['plain', 'mountain', 'swamp']) as $landType => $buildingTypes)
                                        <thead>
                                            <tr>
                                                <th colspan=2>
                                                    <h4>{{ ucwords($landType) }}</h4>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Building</th>
                                                <th class="text-center">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($buildingTypes as $buildingType)
                                                <tr>
                                                    <td>
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}">
                                                            {{ $buildingHelper->getBuildingName($buildingType) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="construct[building_{{ $buildingType }}]" class="form-control text-center" placeholder="{{ array_get($defaultBuildings, $buildingType, 0) }}" value="{{ $selectedDominion->{"building_{$buildingType}"} ?: null }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    @endforeach
                                    <thead>
                                        <tr>
                                            <th colspan=2>
                                                <h4>Total Buildings</h4>
                                            </th>
                                        </tr>
                                        <tr>
                                            <th class="text-center">Available</th>
                                            <th class="text-center">Selected</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-center">
                                                <span id="total_land">{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span id="total_buildings">600</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="col-md-12 col-lg-6">
                                <table class="table table-condensed">
                                    <colgroup>
                                        <col width="50%">
                                        <col width="50%">
                                    </colgroup>
                                    @foreach ($buildingsByLandType->only(['cavern', 'forest', 'hill', 'water']) as $landType => $buildingTypes)
                                        <thead>
                                            <tr>
                                                <th colspan=2>
                                                    <h4>{{ ucwords($landType) }}</h4>
                                                </th>
                                            </tr>
                                            <tr>
                                                <th>Building</th>
                                                <th class="text-center">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($buildingTypes as $buildingType)
                                                <tr>
                                                    <td>
                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $buildingHelper->getBuildingHelpString($buildingType) }}">
                                                            {{ $buildingHelper->getBuildingName($buildingType) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="construct[building_{{ $buildingType }}]" class="form-control text-center" placeholder="{{ array_get($defaultBuildings, $buildingType, 0) }}" value="{{ $selectedDominion->{"building_{$buildingType}"} ?: null }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>

                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }} disabled>Build</button>
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
                    <p>You can choose to start with whichever buildings you want.</p>
                    <p>You have {{ number_format($landCalculator->getTotalLand($selectedDominion)) }} acres of land.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var submitButtonElement = $('button[type=submit]');
            var totalBuildingsElement = $('#total_buildings');
            var totalLandElement = $('#total_land');

            function sumBuildingInputs() {
                var total = 0;
                $('input[name*="construct"]').each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                return total;
            }

            function updateTotal() {
                var total = sumBuildingInputs();
                totalBuildingsElement.text(total);
                var expectedTotal = parseInt(totalLandElement.text());
                if (total == expectedTotal) {
                    totalBuildingsElement.removeClass('text-red');
                    totalBuildingsElement.addClass('text-green');
                    submitButtonElement.removeAttr('disabled');
                } else {
                    totalBuildingsElement.removeClass('text-green');
                    totalBuildingsElement.addClass('text-red');
                    submitButtonElement.attr('disabled', 'disabled');
                }
            }

            updateTotal();

            $('input[name*="construct"]').on('change keyup', function() {
                updateTotal();
            });
        })(jQuery);
    </script>
@endpush
