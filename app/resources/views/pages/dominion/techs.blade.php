@extends('layouts.master')

@section('page-header', 'Technological Advances')

@section('content')
    <?php
        $techVersion = $selectedDominion->round->tech_version;
        $techs = $techHelper->getTechs($techVersion);
        $unlockedTechs = $selectedDominion->techs->pluck('key')->all();
        $permanentTechKeys = $selectedDominion->techs->filter(function ($tech) {
            return $tech->pivot->source_id === null;
        })->pluck('key')->all();
        $temporaryTechKeys = $selectedDominion->techs->filter(function ($tech) {
            return $tech->pivot->source_id !== null;
        })->pluck('key')->all();
        $currentTempTech = $selectedDominion->techs->filter(function ($tech) {
            return $tech->pivot->source_id !== null;
        })->first();
        $availableTechs = $techs->filter(function ($tech) use ($permanentTechKeys) {
            return $tech->key !== 'tech_7_5' && !in_array($tech->key, $permanentTechKeys);
        });

        $tempTechCooldownHours = 0;
        if ($currentTempTech) {
            $selectedAt = $currentTempTech->pivot->created_at->startOfHour();
            $cooldownEnd = $selectedAt->copy()->addHours(96);
            if (now()->lt($cooldownEnd)) {
                $tempTechCooldownHours = now()->diffInHours($cooldownEnd, false) + 1;
            }
        }
    ?>

    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if ($selectedDominion->getWonderPerkValue('temporary_tech') > 0)
                <form action="{{ route('dominion.techs.temporary-tech') }}" method="post" role="form">
                    @csrf
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-pyramids"></i> Planar Gates - Temporary Tech</h3>
                        </div>
                        <div class="box-body">
                            <p>Your realm controls the Planar Gates. Select one unresearched tech to gain its benefits temporarily. Benefits are lost if the wonder changes hands.</p>
                            @if ($currentTempTech)
                                <p>
                                    <strong>Current selection:</strong> {{ $currentTempTech->name }}
                                    <span class="text-muted">({{ $techHelper->getTechDescription($currentTempTech) }})</span>
                                </p>
                                @if ($tempTechCooldownHours > 0)
                                    <p class="text-warning"><i class="fa fa-clock-o"></i> You can change your selection in {{ $tempTechCooldownHours }} {{ str_plural('hour', $tempTechCooldownHours) }}.</p>
                                @endif
                            @endif
                            <div class="row">
                                <div class="col-xs-9 col-lg-10">
                                    <select name="tech" id="temporary-tech" class="form-control select2" style="width: 100%" data-placeholder="Select a tech" {{ ($selectedDominion->isLocked() || $tempTechCooldownHours > 0) ? 'disabled' : null }}>
                                        <option></option>
                                        @foreach ($availableTechs->sortBy('name') as $tech)
                                            <option value="{{ $tech->key }}" {{ ($currentTempTech && $currentTempTech->key === $tech->key) ? 'selected' : '' }}>
                                                {{ $tech->name }} ({{ $techHelper->getTechDescription($tech) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-xs-3 col-lg-2">
                                    <button type="submit" class="btn btn-success" {{ ($selectedDominion->isLocked() || $tempTechCooldownHours > 0) ? 'disabled' : null }}>
                                        Select
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            @endif

            <form action="{{ route('dominion.techs') }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-flask"></i> Technological Advances</h3>
                    </div>
                    <div class="box-body no-padding">
                        <div class="row">
                            @if ($techVersion !== 1)
                                <div class="col-md-6">
                                    @include('partials.dominion.tech-tree', ['version' => $techVersion])
                                </div>
                            @endif
                            <div class="{{ $techVersion == 1 ? 'col-md-12' : 'col-md-6' }}">
                                @include('partials.dominion.info.techs-combined', ['data' => $selectedDominion->techs->pluck('name', 'key'), 'version' => $techVersion])
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ ($techCalculator->getTechCost($selectedDominion) > $selectedDominion->resource_tech || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
                    </div>
                </div>

                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-flask"></i> Technological Advances</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table">
                            <colgroup>
                                <col width="5%">
                                <col width="20%">
                                <col>
                                <col width="20%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Requires one of</th>
                                </tr>
                            </thead>
                            @foreach ($techs as $tech)
                                <tr class="{{ $techCalculator->hasPrerequisites($selectedDominion, $tech) ? 'text-default' : 'text-muted' }} {{ empty($tech->prerequisites) && !in_array($tech->key, $unlockedTechs) ? 'active' : null }}">
                                    <td class="text-center{{ in_array($tech->key, $permanentTechKeys) ? ' text-green' : (in_array($tech->key, $temporaryTechKeys) ? ' text-yellow' : null) }}">
                                        @if (in_array($tech->key, $permanentTechKeys))
                                            <i class="fa fa-check"></i>
                                        @else
                                            <input type="radio" name="key" id="tech_{{ $tech->key }}" value="{{ $tech->key }}" {{ $techCalculator->hasPrerequisites($selectedDominion, $tech) ? null : 'disabled' }}>
                                        @endif
                                    </td>
                                    <td>
                                        <label for="tech_{{ $tech->key }}" style="font-weight: normal;">
                                            {{ $tech->name }}
                                        </label>
                                    </td>
                                    <td>
                                        <label for="tech_{{ $tech->key }}" style="font-weight: normal;">
                                            {!! $techHelper->getTechDescription($tech, '<br/>') !!}
                                        </label>
                                    </td>
                                    <td>
                                        @if ($tech->prerequisites)
                                            @foreach ($tech->prerequisites as $key)
                                                {{ $techs[$key]->name }}@if (!$loop->last),<br/>@endif
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ ($techCalculator->getTechCost($selectedDominion) > $selectedDominion->resource_tech || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
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
                    @php($techProgress = min(100, $selectedDominion->resource_tech / $techCalculator->getTechCost($selectedDominion) * 100))
                    <p>You can obtain technical advancements by reaching appropriate levels of research points. The base cost of each advancement is 2.5x highest land achieved and increases by 50 after each unlock (min 3750). Most advancements require unlocking another before you can select them.</p>
                    <p><a href="{{ route('scribes.techs') }}?{{ implode('&', array_map(function($key) { return str_replace('tech_', '', $key); }, $unlockedTechs)) }}">View as Interactive Tree</a> in the Scribes.</p>
                    <p>If you pick a tech that has the same bonus as another tech, you will receive the total bonus from both.</p>
                    <p>You have unlocked <b>{{ count($permanentTechKeys) }} techs</b>.
                        @if (count($temporaryTechKeys) > 0)
                            <span class="text-yellow">(+{{ count($temporaryTechKeys) }} temporary)</span>
                        @endif
                    </p>
                    <p>You have <b>{{ number_format($selectedDominion->resource_tech) }} research points</b> out of the {{ number_format($techCalculator->getTechCost($selectedDominion)) }} required to unlock a new tech.</p>
                    <p>You produce <b>{{ $productionCalculator->getTechProduction($selectedDominion) }}</b> research points per hour.</p>
                    <div class="progress" style="margin-bottom: 0px;">
                        <div class="progress-bar progress-bar-success" role="progressbar" style="width: {{ number_format($techProgress) }}%">
                            @if ($techProgress > 5)
                                {{ number_format($techProgress, 2) }}%
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-styles')
    <style type="text/css">
        .edge {
            stroke: lightgray;
        }
        .edge.active {
            stroke: black;
        }
        .vertex {
            fill: white;
            stroke: gray;
        }
        .vertex.active {
            stroke: black;
        }
        .vertex.selected {
            fill: lightskyblue;
            stroke: black;
        }
        .vertex:hover {
            cursor: pointer;
            fill: orangered;
        }
        .vertex.active:hover {
            fill: lightgreen;
            stroke: black;
        }
        .skin-classic .edge {
            stroke: gray;
        }
        .skin-classic .edge.active {
            stroke: #dddddd;
        }
        .skin-classic .vertex {
            fill: black;
            stroke: gray;
        }
        .skin-classic .vertex.active {
            stroke: #dddddd;
        }
        .skin-classic .vertex.selected {
            fill: #006C81;
            stroke: #dddddd;
        }
        .skin-classic .vertex:hover {
            cursor: pointer;
            fill: #dd4b39;
        }
        .skin-classic .vertex.active:hover {
            fill: #007D1C;
            stroke: #dddddd;
        }

        .vertex.temporary {
            fill: khaki;
            stroke: goldenrod;
        }
        .vertex.temporary:hover {
            fill: lightgreen;
            cursor: pointer;
        }
        .vertex.selection {
            fill: lightgreen;
        }
        .skin-classic .vertex.temporary {
            fill: #8B7500;
            stroke: #B8960C;
        }
        .skin-classic .vertex.temporary:hover {
            fill: #007D1C;
            cursor: pointer;
        }
        .skin-classic .vertex.selection {
            fill: #006C81;
        }
    </style>
@endpush


@push('inline-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#temporary-tech').select2();

            var techPerkStrings = {!! json_encode($techHelper->getTechPerkStrings()) !!};

            function updateTree() {
                // Clear all edges
                $('.edge').removeClass('active');
                // Clear all vertices
                $('.vertex').removeClass('active');

                // Highlight starting vertices
                $('.vertex.starting').addClass('active');

                // Highlight all adjacent edges
                $('.vertex.selected').each(function() {
                    var id = $(this).attr('id');
                    $('.'+id).addClass('active');
                });

                // Highlight all adjacent vertices
                $('.edge.active').each(function() {
                    // Highlight all adjacent vertices
                    var classes = $(this).attr('class');
                    $.each(classes.split(" "), function(idx, className) {
                        if (className !== 'edge' && className !== 'active') {
                            $('#'+className).addClass('active');
                        }
                    });
                });
            }

            var permanentTechs = {!! json_encode(array_values($permanentTechKeys)) !!};
            var temporaryTechs = {!! json_encode(array_values($temporaryTechKeys)) !!};
            permanentTechs.forEach(function(value) {
                $('#'+value).addClass('selected');
            });
            temporaryTechs.forEach(function(value) {
                var radio = $('#tech_'+value);
                if (radio.length && !radio.prop('disabled')) {
                    $('#'+value).addClass('temporary active');
                } else {
                    $('#'+value).addClass('temporary');
                }
            });
            updateTree();

            $('.vertex').click(function() {
                if (!$(this).hasClass('active')) return;

                var techId = $(this).attr('id');
                if (permanentTechs.indexOf(techId) !== -1) return;

                if ($(this).hasClass('selection')) {
                    $('#tech_'+techId).get(0).scrollIntoView();
                    return;
                }

                // Reset newly selected node
                $('.selection').removeClass('selected');
                $('.selection').removeClass('selection');

                // Set node as newly selected
                $(this).addClass('selected');
                $(this).addClass('selection');
                $('#tech_'+techId).prop('checked', true);
            });

            window.SVGElement = null;
            $('.vertex').tooltip({
                'container': 'body',
                'html': true,
                'placement': 'bottom',
            });
        });
    </script>
@endpush
