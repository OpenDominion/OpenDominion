@extends('layouts.master')

@section('page-header', 'Military')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Military</h3>
                </div>
                <form action="{{ route('dominion.military.train') }}" method="post" role="form">
                    @csrf
                    @include('partials.user.client-id-field')
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col>
                                <col width="10%">
                                <col width="10%">
                                <col width="10%">
                                <col width="20%">
                                <col width="15%">
                                <col width="15%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Unit</th>
                                    <th class="text-center">OP / DP</th>
                                    <th class="text-center">Trained</th>
                                    <th class="text-center">Training</th>
                                    <th class="text-center">Cost Per Unit</th>
                                    <th class="text-center">Max Trainable</th>
                                    <th class="text-center">Train</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($unitHelper->getUnitTypes() as $unitType)
                                    <tr>
                                        <td>
                                            {!! $unitHelper->getUnitTypeIconHtml($unitType, $selectedDominion->race) !!}
                                            <span data-toggle="tooltip" data-placement="top" title="{{ $unitHelper->getUnitHelpString($unitType, $selectedDominion->race) }}">
                                                {{ $unitHelper->getUnitName($unitType, $selectedDominion->race) }}
                                            </span>
                                        </td>
                                        @if (in_array($unitType, ['unit1', 'unit2', 'unit3', 'unit4']))
                                            @php
                                                $unit = $selectedDominion->race->units->filter(function ($unit) use ($unitType) {
                                                    return ($unit->slot == (int)str_replace('unit', '', $unitType));
                                                })->first();

                                                $offensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'offense');
                                                $defensivePower = $militaryCalculator->getUnitPowerWithPerks($selectedDominion, null, null, $unit, 'defense');

                                                $hasDynamicOffensivePower = $unit->perks->filter(static function ($perk) {
                                                    return starts_with($perk->key, ['offense_from_', 'offense_staggered_', 'offense_vs_']);
                                                })->count() > 0;
                                                $hasDynamicDefensivePower = $unit->perks->filter(static function ($perk) {
                                                    return starts_with($perk->key, ['defense_from_', 'defense_staggered_', 'defense_vs_']);
                                                })->count() > 0;
                                            @endphp
                                            <td class="text-center">
                                                @if ($offensivePower == 0)
                                                    <span class="text-muted">0</span>
                                                @else
                                                    {{ (strpos($offensivePower, '.') !== false) ? number_format($offensivePower, 1) : number_format($offensivePower) }}{{ $hasDynamicOffensivePower ? '*' : null }}
                                                @endif
                                                /
                                                @if ($defensivePower == 0)
                                                    <span class="text-muted">0</span>
                                                @else
                                                    {{ (strpos($defensivePower, '.') !== false) ? number_format($defensivePower, 1) : number_format($defensivePower) }}{{ $hasDynamicDefensivePower ? '*' : null }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($militaryCalculator->getTotalUnitsForSlot($selectedDominion, $unit->slot)) }}
                                            </td>
                                        @else
                                            <td class="text-center">&nbsp;</td>
                                            <td class="text-center">
                                                {{ number_format($selectedDominion->{'military_' . $unitType}) }}
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            {{ number_format($queueService->getTrainingQueueTotalByResource($selectedDominion, "military_{$unitType}")) }}
                                        </td>
                                        <td class="text-center">
                                            {{ $unitHelper->getUnitCostStringFromArray($trainingCalculator->getTrainingCostsPerUnit($selectedDominion)[$unitType]) }}
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($trainingCalculator->getMaxTrainable($selectedDominion)[$unitType]) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="input-group">
                                                <input type="number" name="train[military_{{ $unitType }}]" class="form-control text-center" placeholder="0" min="0" max="{{ $trainingCalculator->getMaxTrainable($selectedDominion)[$unitType] }}" value="{{ old('train.' . $unitType) }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-primary train-max" data-type="military_{{ $unitType }}" type="button" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                        Max
                                                    </button>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Train</button>
                        <div class="pull-right">
                            You have <strong>{{ number_format($selectedDominion->military_draftees) }}</strong> {{ str_plural('draftee', $selectedDominion->military_draftees) }} available to train.
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.military') }}" class="pull-right">Military Advisor</a>
                </div>
                <div class="box-body">
                    <p>Here you can train your draftees into stronger military units. Until your draft rate is met, 1% of your peasants will join your military each hour.</p>
                    <p>Training specialist units take <b>9 hours</b> to process, while training your other units take <b>12 hours</b>.</p>
                    <p><a href="{{ route('dominion.military.release') }}" class="btn btn-danger">Release Troops</a></p>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Draft Rate</h3>
                </div>
                <form action="{{ route('dominion.military.change-draft-rate') }}" method="post" role="form">
                    @csrf
                    <div class="box-body table-responsive no-padding">
                        <table class="table" style="margin-bottom: 0px;">
                            <colgroup>
                                <col width="50%">
                                <col width="50%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>Peasants</td>
                                    <td>
                                        {{ number_format($selectedDominion->peasants) }}
                                        ({{ number_format($populationCalculator->getPopulationPeasantPercentage($selectedDominion), 2) }}%)
                                    </td>
                                </tr>
                                <tr>
                                    <td>Military</td>
                                    <td>
                                        {{ number_format($populationCalculator->getPopulationMilitary($selectedDominion)) }}
                                        ({{ number_format($populationCalculator->getPopulationMilitaryPercentage($selectedDominion), 2) }}%)
                                    </td>
                                </tr>
                                <tr>
                                    <td>Target:</td>
                                    <td>
                                        <input type="number" name="draft_rate" class="form-control text-center"
                                               style="display: inline-block; width: 80px;" placeholder="0" min="0"
                                               max="90"
                                               value="{{ $selectedDominion->draft_rate }}" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                        %
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            Change
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-sword"></i> Units in training and home</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.military-training-table', ['data' => $infoMapper->mapMilitary($selectedDominion, false), 'isOp' => false, 'race' => $selectedDominion->race ])
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Units returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.military-returning-table', ['data' => $infoMapper->mapMilitary($selectedDominion, false), 'isOp' => false, 'race' => $selectedDominion->race ])
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Resources returning from battle</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.resources-incoming-table', ['data' => $infoMapper->mapResources($selectedDominion)])
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-clock-o"></i> Incoming land breakdown</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @include('partials.dominion.info.land-incoming-table', ['data' => $infoMapper->mapLand($selectedDominion), 'race' => $selectedDominion->race])
                </div>
            </div>
        </div>

    </div>
@endsection


@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.train-max').click(function(e) {
                var troopType = $(this).data('type');
                var troopInput = $('input[name=train\\['+troopType+'\\]]');
                var maxAmount = troopInput.attr('max');

                $('input[name^=train]').val('');
                troopInput.val(maxAmount);
            });
        })(jQuery);
    </script>
    @include('partials.user.client-id-script')
@endpush
