@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
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
