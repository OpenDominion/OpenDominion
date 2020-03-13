@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Espionage</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 col-md-12">
                    <p>Espionage is when you send your spies to take actions against a target in so-called spy-ops.</p>
                    <em>
                        <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Espionage">wiki</a>.</p>
                    </em>
                </div>
            </div>
        </div>
    </div>
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Operations</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12 col-md-6">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Information gathering</h4>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="200px">
                            <col>
                        </colgroup>
                        <tbody>
                            @foreach($espionageHelper->getInfoGatheringOperations()->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>&nbsp;</p>
                </div>
                <div class="col-md-12 col-md-6">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Theft</h4>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="200px">
                            <col>
                        </colgroup>
                        <tbody>
                            @foreach($espionageHelper->getResourceTheftOperations()->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>&nbsp;</p>
                </div>
                <div class="col-md-12 col-md-6">
                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Black Ops</h4>
                    <table class="table table-striped" style="margin-bottom: 0">
                        <colgroup>
                            <col width="200px">
                            <col>
                        </colgroup>
                        <tbody>
                            @foreach($espionageHelper->getHostileOperations()->sortBy('name') as $operation)
                                <tr>
                                    <td>{{ $operation['name'] }}</td>
                                    <td>{{ $operation['description'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p>&nbsp;</p>
                </div>
            </div>
        </div>
    </div>
@endsection
