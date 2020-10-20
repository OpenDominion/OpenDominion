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
                    <p>The cost of each advancement scales according to your highest land size or 35% of your total land conqured (whichever is higher) with a minimum of 3780 points. Some advancements require others before you can select them. Please consult the tech tree below.</p>
                    <p>If you pick a tech that has the same, but higher, bonus as another tech, as several do, only the highest technology bonus counts. It overrides the lesser bonus (they do not stack).</p>
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
                            <circle r="5" cx="{{ $techHelper->getX($tech) }}" cy="{{ $techHelper->getY($tech) }}" class="vertex {{ empty($tech->prerequisites) ? 'active starting' : null }}" id="{{ $tech->key }}" title="<b>{{ $tech->name }}:</b><br/>{{ $techHelper->getTechDescription($tech, '<br/>') }}" />
                        @endforeach
                    </svg>
                </div>
                <div class="col-md-6">
                    <h5>Techs Selected <span id="tech-total">0</span></h5>
                    <h5 style="margin-top: 20px;">Total Bonuses</h5>
                    <p id="tech-bonuses"></p>
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
            $('.vertex').click(function() {
                //if (!$(this).hasClass('active')) return;

                // Clear all edges
                $('.edge').removeClass('active');
                // Clear all vertices
                $('.vertex').removeClass('active');
                // Toggle vertex
                $(this).toggleClass('selected');

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
                    var perks = $(node).data('original-title').split(',');
                    perks.forEach(function(value, index, array) {
                        var perkString = value.replace(/\s*\([^)]*\)\s*/g, "");
                        var matches = perkString.match(/([\+\-\d\.]+)(.*)/);
                        if (matches[2] in techBonuses) {
                            techBonuses[matches[2]] += parseFloat(matches[1]);
                        } else {
                            techBonuses[matches[2]] = parseFloat(matches[1]);
                        }
                    });
                });
                var techHtml = '';
                for (let key in techBonuses) {
                    if (techBonuses[key] > 0) techHtml += '+';
                    techHtml += techBonuses[key]+key+"<br/>";
                }
                $('#tech-bonuses').html(techHtml);
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
