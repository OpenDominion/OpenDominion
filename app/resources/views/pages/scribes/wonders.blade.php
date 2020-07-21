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
                    <p>All wonders will begin each round in realm 0, with a starting power of 250,000. Once rebuilt, wonder power depends on the damage your realm did to it and time into the round.</p>
                    <p>Each dominion in a realm destroying a wonder that is not in realm 0 receives 100 prestige.</p>
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
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $wonders = $wonderHelper->getWonders(); @endphp
                            @foreach($wonders as $wonder)
                                <tr>
                                    <td>{{ $wonder->name }}</td>
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
