@extends('layouts.master')

@section('page-header', 'Technological Advances')

@section('content')
    @php($unlockedTechs = $selectedDominion->techs->pluck('key')->all())

    <form action="{{ route('dominion.techs') }}" method="post" role="form">
    @csrf
        <div class="row">

            <div class="col-sm-12 col-md-9">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-flask"></i> Technological Advances</h3>
                    </div>
                    <div class="box-body no-padding">
                        <div class="row">
                            <div class="col-md-6">
                                @include('partials.dominion.tech-tree')
                            </div>
                            <div class="col-md-6">
                                @include('partials.dominion.info.techs-combined', ['data' => $selectedDominion->techs->pluck('name', 'key')])
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ ($techCalculator->getTechCost($selectedDominion) > $selectedDominion->resource_tech || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-md-3">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Information</h3>
                    </div>
                    <div class="box-body">
                        @php($techProgress = min(100, $selectedDominion->resource_tech / $techCalculator->getTechCost($selectedDominion) * 100))
                        <p>You can obtain technical advancements by reaching appropriate levels of research points. The base cost of each advancement is 3600 + 0.65x highest land achieved and increases by 100 after each unlock (min 3900). Most advancements require unlocking another before you can select them.</p>
                        <p><a href="{{ route('scribes.techs') }}?{{ implode('&', array_map(function($key) { return str_replace('tech_', '', $key); }, $unlockedTechs)) }}">View as Interactive Tree</a> in the Scribes.</p>
                        <p>If you pick a tech that has the same bonus as another tech, you will receive the total bonus from both.</p>
                        <p>You have unlocked <b>{{ $selectedDominion->techs->count() }} techs</b>.</p>
                        <p>You have <b>{{ number_format($selectedDominion->resource_tech) }} research points</b> out of the {{ number_format($techCalculator->getTechCost($selectedDominion)) }} required to unlock a new tech.</p>
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
        <div class="row">

            <div class="col-sm-12 col-md-9">
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
                                <tr class="{{ $techCalculator->hasPrerequisites($selectedDominion, $tech) ? 'text-default' : 'text-muted' }}{{ in_array($tech->key, $unlockedTechs) ? ' text-green' : null }} {{ empty($tech->prerequisites) && !in_array($tech->key, $unlockedTechs) ? 'active' : null }}">
                                    <td class="text-center">
                                        @if(in_array($tech->key, $unlockedTechs))
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
                                                {{ $techs[$key]->name }}@if(!$loop->last),<br/>@endif
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
            </div>

        </div>
    </form>
@endsection

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

        .vertex.selection {
            fill: lightgreen;
        }
        .skin-classic .vertex.selection {
            fill: #006C81;
        }
    </style>
@endpush


@push('inline-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
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

            var unlockedTechs = {!! json_encode($unlockedTechs) !!};
            unlockedTechs.forEach(function(value, index, array) {
                $('#'+value).addClass('selected');
            });
            updateTree();

            $('.vertex').click(function() {
                if (!$(this).hasClass('active')) return;

                var techId = $(this).attr('id');
                if (unlockedTechs.indexOf(techId) !== -1) return;

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
