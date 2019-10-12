@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Operations</h3>
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
                                <th>Information Operation</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($espionageHelper->getInfoGatheringOperations() as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
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
                                <th>Theft Operation</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($espionageHelper->getResourceTheftOperations() as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
{{--            <div class="row">--}}
{{--                <div class="col-md-12 col-md-12">--}}
{{--                    <table class="table table-striped" style="margin-bottom: 0">--}}
{{--                        <colgroup>--}}
{{--                            <col>--}}
{{--                            <col>--}}
{{--                            <col>--}}
{{--                        </colgroup>--}}
{{--                        <thead>--}}
{{--                            <tr>--}}
{{--                                <th>Black Operation</th>--}}
{{--                                <th>&nbsp;</th>--}}
{{--                            </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                            @foreach($espionageHelper->getBlackOperations() as $operation)--}}
{{--                                <tr>--}}
{{--                                    <td>{{ $operation['name'] }}</td>--}}
{{--                                    <td>{{ $operation['description'] }}</td>--}}
{{--                                </tr>--}}
{{--                            @endforeach--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="row">--}}
{{--                <div class="col-md-12 col-md-12">--}}
{{--                    <table class="table table-striped" style="margin-bottom: 0">--}}
{{--                        <colgroup>--}}
{{--                            <col>--}}
{{--                            <col>--}}
{{--                            <col>--}}
{{--                        </colgroup>--}}
{{--                        <thead>--}}
{{--                            <tr>--}}
{{--                                <th>War Operation</th>--}}
{{--                                <th>&nbsp;</th>--}}
{{--                            </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                            @foreach($espionageHelper->getWarOperations() as $operation)--}}
{{--                                <tr>--}}
{{--                                    <td>{{ $operation['name'] }}</td>--}}
{{--                                    <td>{{ $operation['description'] }}</td>--}}
{{--                                </tr>--}}
{{--                            @endforeach--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
    </div>
@endsection
