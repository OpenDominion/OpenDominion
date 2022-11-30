@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Wonders</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Wonders provide bonuses to all dominions in the controlling realm and are acquired by destroying and rebuilding them.</p>
                    <p>The first wave of wonders will appear at the start of the round. An additional wonder will appear every 48 hours starting on Day 3. Once rebuilt, wonder power depends on the damage your realm did to it and time into the round.</p>
                    <p> When attacking wonders, your offense is <b>unmodded</b> (except by morale) and always suffers <b>3.5% casualties</b> (including immortal units). Each dominion that attacks a wonder controlled by another realm is awarded prestige if they destroy and rebuild it in their realm.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Wonders">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Wonders of the World</h3>
        </div>
        <div class="box-body table-responsive">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="200">
                            <col width="200">
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Starting Power</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th colspan="3">Tier 1</th>
                            </tr>
                            @php $wonders = $wonderHelper->getWonders()->where('power', 150000); @endphp
                            @foreach($wonders as $wonder)
                                <tr>
                                    <td>{{ $wonder->name }}</td>
                                    <td>{{ number_format($wonder->power) }}</td>
                                    <td>{{ $wonderHelper->getWonderDescription($wonder) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="3">Tier 2</th>
                            </tr>
                            @php $wonders = $wonderHelper->getWonders()->where('power', 75000); @endphp
                            @foreach($wonders as $wonder)
                                <tr>
                                    <td>{{ $wonder->name }}</td>
                                    <td>{{ number_format($wonder->power) }}</td>
                                    <td>{{ $wonderHelper->getWonderDescription($wonder) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
