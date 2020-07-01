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
                    <p>The tech tree is a map of advancements that your dominion can obtain as you grow in size. You can obtain technical advancements by reaching appropriate levels of research points.</p>
                    <p>The costs of each advancement will scale with your land size, and has a minimum of 3780 points. Some advancements require others before you can select them. Please consult the tech tree below.</p>
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
                <div class="col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="200">
                            <col>
                            <col width="200">
                            <col>
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
                            @foreach($techs as $tech)
                                <tr>
                                    <td>{{ $tech->name }}</td>
                                    <td>{{ $techHelper->getTechDescription($tech) }}</td>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
