@extends('layouts.topnav')

@section('content')
    @php
        if ($legacy) {
            $techVersion = 1;
        } else {
            $techVersion = $techHelper::CURRENT_VERSION;
        }
        $techs = $techHelper->getTechs($techVersion);
    @endphp

    @include('partials.scribes.nav')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <span class="card-title">Techs</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <p>The tech tree is a map of advancements that your dominion obtain as you reach appropriate levels of research points.</p>
                    <p>The base cost of each advancement is 2.5x highest land achieved and increases by 50 after each unlock (min 3750). Most advancements require unlocking another before you can select them. Please consult the tech tree below.</p>
                    <p>If you pick a tech that has the same bonus as another tech, you will receive the total bonus from both.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Teching">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <div class="float-end">
                @if ($legacy)
                    <a href="{{ route('scribes.techs') }}">View Latest Techs</a>
                @else
                    <a href="{{ route('scribes.legacy-techs') }}">View Legacy Techs</a>
                @endif
            </div>
        </div>
    </div>
    @if ($techVersion !== 1)
        <div class="card">
            <div class="card-header">
                <span class="card-title">Technological Advances</span>
            </div>
            <div class="card-body table-responsive">
                <div class="row">
                    <div class="col-md-6">
                        @include('partials.dominion.tech-tree', ['version' => $techVersion])
                    </div>
                    <div class="col-md-6">
                        <h5>Techs Selected: <span id="tech-total">0</span></h5>
                        <h5 style="margin-top: 20px;">Total Bonuses</h5>
                        <table class="table table-sm">
                            <tbody id="tech-bonuses"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="card">
        <div class="card-header">
            <span class="card-title">Technological Advances</span>
        </div>
        <div class="card-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="200">
                            <col>
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Requires one of</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($techs as $tech)
                                <tr>
                                    <td>{{ $tech->name }}</td>
                                    <td>{!! $techHelper->getTechDescription($tech, ',<br/>') !!}</td>
                                    <td>
                                        @if ($tech->prerequisites)
                                            @foreach ($tech->prerequisites as $prereq)
                                                @if (isset($techs[$prereq]))
                                                    {{ $techs[$prereq]->name }}@if (!$loop->last),<br/>@endif
                                                @endif
                                            @endforeach
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
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
        [data-bs-theme="dark"] .edge {
            stroke: var(--od-text-secondary, gray);
        }
        [data-bs-theme="dark"] .edge.active {
            stroke: var(--od-text-emphasis, #ddd);
        }
        [data-bs-theme="dark"] .vertex {
            fill: var(--od-deep, black);
            stroke: var(--od-text-secondary, gray);
        }
        [data-bs-theme="dark"] .vertex.active {
            stroke: var(--od-text-emphasis, #ddd);
        }
        [data-bs-theme="dark"] .vertex.selected {
            fill: var(--od-info, #006C81);
            stroke: var(--od-text-emphasis, #ddd);
        }
        [data-bs-theme="dark"] .vertex:hover {
            cursor: pointer;
            fill: var(--od-danger, #dd4b39);
        }
        [data-bs-theme="dark"] .vertex.active:hover {
            fill: var(--od-success, #007D1C);
            stroke: var(--od-text-emphasis, #ddd);
        }
    </style>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            var techPerkStrings = {!! json_encode($techHelper->getTechPerkStrings()) !!};

            function updateTree() {
                //if (!$(this).hasClass('active')) return;

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

                // Update total
                $('#tech-total').html($('.vertex.selected').length);

                // Update bonuses
                var techBonuses = [];
                $('.vertex.selected').each(function(idx, node) {
                    var perks = $(node).data('perks');
                    Object.keys(perks).forEach(function(value, index, array) {
                        if (value in techBonuses) {
                            techBonuses[value] += parseFloat(perks[value]);
                        } else {
                            techBonuses[value] = parseFloat(perks[value]);
                        }
                    });
                });
                var techPerks = Object.keys(techBonuses).sort();
                var techHtml = '';
                for (let key in techPerks) {
                    techHtml += "<tr><td class='text-end'>";
                    if (techBonuses[techPerks[key]] > 0) techHtml += '+';
                    techHtml += techBonuses[techPerks[key]];
                    techHtml += "</td><td>";
                    techHtml += techPerkStrings[techPerks[key]].replace('%g%', '').replace('%+g%', '').replace('%+g', '');
                    techHtml += "</td></tr>";
                }
                $('#tech-bonuses').html(techHtml);
            }

            function loadQuerystring() {
                var techArray = location.search.match(/\d+_\d+/g);
                if (techArray) {
                    techArray.forEach(function(pos) {
                        $('#tech_'+pos).addClass('selected');
                    });
                }
                updateTree();
            }

            $('.vertex').click(function() {
                // Toggle vertex
                $(this).toggleClass('selected');
                updateTree();
                var selectedNodes = $.map($('.vertex.selected'), function(node) {
                    return node.id.replace('tech_', '');
                });
                history.pushState(null, null, '?'+selectedNodes.join('&'));
            });

            window.SVGElement = null;
            $('.vertex').tooltip({
                'container': 'body',
                'html': true,
                'placement': 'bottom',
            });

            $(window).on('popstate', function() {
                $('.vertex').removeClass('selected');
                loadQuerystring();
            });

            // Get querystring params on initial load
            loadQuerystring();
        });
    </script>
@endpush
