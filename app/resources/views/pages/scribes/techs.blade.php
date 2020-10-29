@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Techs</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>The tech tree is a map of advancements that your dominion obtain as you reach appropriate levels of research points.</p>
                    <p>The cost of each advancement is 10,000. Most advancements require unlocking another before you can select them. Please consult the tech tree below.</p>
                    <p>If you pick a tech that has the same bonus as another tech, you will receive the total bonus from both.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Teching">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Technological Advances</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-6">
                    <svg class="graph" viewBox="0 0 220 220" role="img">
                        @php $techs = $techHelper->getTechs(); @endphp
                        @foreach ($techs as $tech)
                            @foreach ($tech->prerequisites as $prereq)
                                @if (isset($techs[$prereq]))
                                    <line x1="{{ $techHelper->getX($tech) }}" y1="{{ $techHelper->getY($tech) }}" x2="{{ $techHelper->getX($techs[$prereq]) }}" y2="{{ $techHelper->getY($techs[$prereq]) }}" class="edge {{ $tech->key }} {{ $techs[$prereq]->key }}" />
                                @endif
                            @endforeach
                        @endforeach
                        @foreach ($techs as $tech)
                            <circle r="5" cx="{{ $techHelper->getX($tech) }}" cy="{{ $techHelper->getY($tech) }}"
                                id="{{ $tech->key }}"
                                class="vertex {{ empty($tech->prerequisites) ? 'active starting' : null }}"
                                title="<b>{{ $tech->name }}:</b><br/>{{ $techHelper->getTechDescription($tech, '<br/>') }}"
                                data-perks="{!! $techHelper->getTechPerkJSON($tech) !!}" />
                        @endforeach
                    </svg>
                </div>
                <div class="col-md-6">
                    <h5>Techs Selected <span id="tech-total">0</span></h5>
                    <h5 style="margin-top: 20px;">Total Bonuses</h5>
                    <table class="table table-condensed">
                        <tbody id="tech-bonuses"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Technological Advances</h3>
        </div>
        <div class="box-body table-responsive">
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
                                <th>Requires</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $techs = $techHelper->getTechs(); @endphp
                            @foreach ($techs as $tech)
                                <tr>
                                    <td>{{ $tech->name }}</td>
                                    <td>{!! $techHelper->getTechDescription($tech, ',<br/>') !!}</td>
                                    <td>
                                        @if ($tech->prerequisites)
                                            @foreach ($tech->prerequisites as $key)
                                                @if (isset($techs[$prereq]))
                                                    {{ $techs[$key]->name }}@if(!$loop->last),<br/>@endif
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
        }
        .vertex.active:hover {
            fill: lightgreen;
            stroke: black;
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
                    techHtml += "<tr><td class='text-right'>";
                    if (techBonuses[techPerks[key]] > 0) techHtml += '+';
                    techHtml += techBonuses[techPerks[key]];
                    techHtml += "</td><td>";
                    techHtml += techPerkStrings[techPerks[key]].replace('%s ', '').replace('%s%', '');
                    techHtml += "</td></tr>";
                }
                $('#tech-bonuses').html(techHtml);
            }

            function loadQuerystring() {
                location.search.match(/\d+_\d+/g).forEach(function(pos) {
                    $('#tech_'+pos).addClass('selected');
                });
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
