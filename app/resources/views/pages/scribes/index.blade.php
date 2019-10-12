@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Races</h3>
        </div>
        <div class="box-body no-padding">
            <div class="row">
                <div class="col-md-12 col-md-6">
                    <div class="box-header with-border">
                        <h4 class="box-title">Good Alignment</h4>
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <tbody>
                            @foreach ($goodRaces as $race)
                                <tr>
                                    <td>
                                        <a href="{{ route('scribes.race', str_slug($race['name'])) }}">{{ $race['name'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col-md-12 col-md-6">
                    <div class="box-header with-border">
                        <h4 class="box-title">Evil Alignment</h4>
                    </div>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <tbody>
                            @foreach ($evilRaces as $race)
                                <tr>
                                    <td>
                                        <a href="{{ route('scribes.race', $race['name']) }}">{{ $race['name'] }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Buildings</h3>
        </div>
        <div class="box-body no-padding">
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col>
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Building</th>
                                <th>Land</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($buildingTypeWithLandType as $buildingType => $landType)
                                <tr>
                                    <td>
                                        {{ ucwords(str_replace('_', ' ', $buildingType)) }}
                                        {!! $buildingHelper->getBuildingImplementedString($buildingType) !!}
                                    </td>
                                    <td>
                                        @if($landType !== NULL)
                                            {!! $landHelper->getLandTypeIconHtml($landType) !!} {{ ucfirst($landType) }}
                                        @else
                                            Race dependant
                                        @endif
                                    </td>
                                    <td>
                                        {!! $buildingHelper->getBuildingHelpString($buildingType)  !!}
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
