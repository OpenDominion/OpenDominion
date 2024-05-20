@extends ('layouts.master')

@section('page-header', 'Calculators')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <form action="{{ route('dominion.calculations') }}" method="post" role="form" class="calculate-form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calculator"></i> General Calculator</h3>
                        <div class="pull-right text-red">
                            Experimental
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Race
                            </div>
                            <div class="col-xs-3">
                                <input type="hidden"
                                        name="attrs[race_id]"
                                        value="{{ $targetDominion->race->id }}" />
                                <input class="form-control text-center"
                                        value="{{ $targetDominion->race->name }}"
                                        readonly />
                            </div>
                            <div class="col-xs-3 text-right">
                                Land
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="calc[land]"
                                        class="form-control text-center"
                                        placeholder="250"
                                        min="0"
                                        value="{{ $landCalculator->getTotalLand($targetDominion) }}"
                                        readonly />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Prestige
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="attrs[prestige]"
                                        class="form-control text-center"
                                        placeholder="250"
                                        min="0"
                                        value="{{ $targetDominion->prestige }}" />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                <b>Buildings</b>
                            </div>
                        </div>

                        @foreach (collect($buildingHelper->getBuildingTypes())->chunk(2) as $chunk)
                            <div class="form-group row">
                                @foreach ($chunk as $building)
                                    <div class="col-xs-3 text-right">
                                        {{ $buildingHelper->getBuildingName($building) }}
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                name="attrs[building_{{ $building }}]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                @if (Request::isMethod('post'))
                                                    value="{{ $targetDominion->{'building_'.$building} }}"
                                                @else
                                                    value="{{ $targetDominion->{'building_'.$building} + $queueService->getConstructionQueueTotalByResource($targetDominion, 'building_'.$building) }}"
                                                @endif
                                                />
                                    </div>
                                @endforeach
                            </div>
                            @if ($loop->last)
                                <div class="form-group row">
                                    <div class="col-xs-3 text-right">
                                        Barren
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                name="calc[barren]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                value="{{ $landCalculator->getTotalBarrenLand($targetDominion) }}" />
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                <b>Improvements</b>
                            </div>
                        </div>

                        @foreach (collect($improvementHelper->getImprovementTypes())->chunk(2) as $chunk)
                            <div class="form-group row">
                                @foreach ($chunk as $improvement)
                                    <div class="col-xs-3 text-right">
                                        {{ $improvementHelper->getImprovementName($improvement) }}
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                name="attrs[improvement_{{ $improvement }}]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                value="{{ $targetDominion->{'improvement_'.$improvement} }}" />
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                <b>Military</b>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                Draftees
                            </div>
                            <div class="col-xs-3 text-left">
                                <input type="number"
                                        name="attrs[military_draftees]"
                                        class="form-control text-center"
                                        placeholder="0"
                                        min="0"
                                        value="{{ $targetDominion->military_draftees }}" />
                            </div>
                        </div>

                        @foreach (collect($unitHelper->getUnitTypes())->chunk(2) as $chunk)
                            <div class="form-group row">
                                @foreach ($chunk as $unit)
                                    <div class="col-xs-3 text-right">
                                        {{ $unitHelper->getUnitName($unit, $targetDominion->race) }}
                                    </div>
                                    <div class="col-xs-3 text-left">
                                        <input type="number"
                                                name="attrs[military_{{ $unit }}]"
                                                class="form-control text-center"
                                                placeholder="0"
                                                min="0"
                                                @if (Request::isMethod('post'))
                                                    value="{{ $targetDominion->{'military_'.$unit} }}"
                                                @else
                                                    value="{{ $targetDominion->{'military_'.$unit} + $queueService->getInvasionQueueTotalByResource($targetDominion, 'military_'.$unit) + $queueService->getTrainingQueueTotalByResource($targetDominion, 'military_'.$unit) }}"
                                                @endif
                                                />
                                    </div>
                                @endforeach
                            </div>
                        @endforeach

                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                <b>Spells</b>
                            </div>
                            <div class="col-xs-9">
                                <a
                                   class="text-bold"
                                   href="#spell-wrapper"
                                   data-toggle="collapse"
                                   data-target="#spell-wrappper"
                                   aria-expanded="false"
                                   aria-controls="tech-wrappper"
                                >
                                    Show/Hide
                                </a>
                            </div>
                        </div>

                        <div id="spell-wrappper" class="collapse">
                            @php
                                $activeSpells = $targetDominion->spells->keyBy('key')->keys();
                            @endphp
                            @foreach ($spellHelper->getSpells($targetDominion->race, 'self')->chunk(2) as $chunk)
                                <div class="form-group row">
                                    @foreach ($chunk as $spell)
                                        <div class="col-xs-3 text-right">
                                            {{ $spell->name }}
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="checkbox"
                                                    step="any"
                                                    name="spells[{{ $spell->key }}]"
                                                    {{ $activeSpells->contains($spell->key) ? 'checked' : null }} />
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        @php
                            $unlockedTechs = $targetDominion->techs->keyBy('key')->keys();
                        @endphp
                        <div class="form-group row">
                            <div class="col-xs-3 text-right">
                                <b>Techs</b>
                            </div>
                            <div class="col-xs-9">
                                <a
                                   class="text-bold"
                                   href="#tech-wrapper"
                                   data-toggle="collapse"
                                   data-target="#tech-wrappper"
                                   aria-expanded="false"
                                   aria-controls="tech-wrappper"
                                >
                                    Show/Hide
                                </a>
                            </div>
                        </div>

                        <div id="tech-wrappper" class="collapse">
                            @foreach ($techHelper->getTechs()->chunk(2) as $chunk)
                                <div class="form-group row">
                                    @foreach ($chunk as $tech)
                                        <div class="col-xs-3 text-right">
                                            {{ $tech->name }}
                                        </div>
                                        <div class="col-xs-3 text-left">
                                            <input type="checkbox"
                                                    step="any"
                                                    name="techs[{{ $tech->key }}]"
                                                    {{ $unlockedTechs->contains($tech->key) ? 'checked' : null }} />
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <!-- TODO: Hero -->

                        <!-- TODO: Wonders -->

                        <!-- TODO: Day In Round -->

                        <div class="row">
                            <div class="col-xs-9 text-right">
                                &nbsp;
                            </div>
                            <div class="col-xs-3 text-right">
                                <button class="btn btn-primary btn-block" type="submit">Calculate</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>I heard you like calculators.</p>
                </div>
            </div>
        </div>
    </div>

    @if (Request::isMethod('post'))
        <div class="row">
            <div class="col-sm-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Production</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            @foreach ([
                                'getPlatinumProduction',
                                'getPlatinumProductionRaw',
                                'getPlatinumProductionMultiplier',
                                'getFoodProduction',
                                'getFoodProductionRaw',
                                'getFoodProductionMultiplier',
                                'getFoodConsumption',
                                'getFoodNetChange',
                                'getLumberProduction',
                                'getLumberProductionRaw',
                                'getLumberProductionMultiplier',
                                'getLumberNetChange',
                                'getManaProduction',
                                'getManaProductionRaw',
                                'getManaProductionMultiplier',
                                'getManaNetChange',
                                'getOreProduction',
                                'getOreProductionRaw',
                                'getOreProductionMultiplier',
                                'getGemProduction',
                                'getGemProductionRaw',
                                'getGemProductionMultiplier',
                                'getTechProduction',
                                'getTechProductionRaw',
                                'getTechProductionMultiplier',
                                'getBoatProduction',
                                'getBoatProductionRaw',
                                'getBoatProductionMultiplier'
                            ] as $method)
                                @php
                                    $result = class_method_display($productionCalculator, $method, $targetDominion)
                                @endphp
                                <tr>
                                    <td>{{ $result['label'] }}:</td>
                                    <td>{{ $result['value'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Population</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            <tr>
                                <td>Peasants:</td>
                                <td>{{ $targetDominion->peasants }}</td>
                            </tr>
                            @foreach ([
                                'getPopulation',
                                'getPopulationMilitary',
                                'getMaxPopulation',
                                'getMaxPopulationRaw',
                                'getMaxPopulationMultiplier',
                                'getMaxPopulationMilitaryBonus',
                                'getPopulationBirth',
                                'getPopulationBirthRaw',
                                'getPopulationBirthMultiplier',
                                'getPopulationPeasantGrowth',
                                'getPopulationDrafteeGrowth',
                                'getPopulationPeasantPercentage',
                                'getPopulationMilitaryPercentage',
                                'getEmploymentJobs',
                                'getPopulationEmployed',
                                'getEmploymentPercentage'
                            ] as $method)
                                @php
                                    $result = class_method_display($populationCalculator, $method, $targetDominion)
                                @endphp
                                <tr>
                                    <td>{{ $result['label'] }}:</td>
                                    <td>{{ $result['value'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Improvements</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            @foreach ($improvementHelper->getImprovementTypes() as $improvement)
                                @php
                                    $result = class_method_display($improvementCalculator, 'getImprovementMultiplierBonus', $targetDominion, [$improvement])
                                @endphp
                                <tr>
                                    <td>{{ ucwords($improvement) }}:</td>
                                    <td>{{ $result['value'] }}</td>
                                </tr>
                            @endforeach
                            @php
                                $result = class_method_display($improvementCalculator, 'getImprovementTotal', $targetDominion)
                            @endphp
                            <tr>
                                <td>{{ $result['label'] }}:</td>
                                <td>{{ $result['value'] }}</td>
                            </tr>
                            @php
                                $result = class_method_display($improvementCalculator, 'getImprovementMultiplier', $targetDominion)
                            @endphp
                            <tr>
                                <td>{{ $result['label'] }}:</td>
                                <td>{{ $result['value'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                &nbsp;
            </div>

            <div class="col-sm-6">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Black Ops</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-condensed">
                            @foreach ([
                                'getSpellVulnerablilityProtectionModifier',
                                'getPeasantVulnerablilityModifier',
                                'getPeasantsProtected',
                                'getPeasantsVulnerable',
                                'getImprovementVulnerablilityModifier',
                                'getImprovementsProtected',
                                'getImprovementsVulnerable'
                            ] as $method)
                                @php
                                    $result = class_method_display($opsCalculator, $method, $targetDominion)
                                @endphp
                                <tr>
                                    <td>{{ $result['label'] }}:</td>
                                    <td>{{ $result['value'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('inline-styles')
    <style type="text/css">
        .calculate-form,
        .calculate-form .table>thead>tr>td,
        .calculate-form .table>tbody>tr>td {
            line-height: 2;
        }
        .calculate-form .form-control {
            height: 30px;
            padding: 3px 6px;
        }
    </style>
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            function updateLandTotal() {
                var landTotal = 0;
                $('input[name^=attrs\\[building_').each(function (i, n) {
                    landTotal += parseInt($(n).val(), 10);
                });
                landTotal += parseInt($('input[name=calc\\[barren\\]]').val(), 10);
                $('input[name=calc\\[land\\]]').val(landTotal);
            }

            $('input[name^=attrs\\[building_').change(function (e) {
                updateLandTotal();
            });

            $('input[name=calc\\[barren\\]]').change(function (e) {
                updateLandTotal();
            });

            updateLandTotal();
        })(jQuery);
    </script>
@endpush
