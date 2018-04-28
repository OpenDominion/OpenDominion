@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">Op Center</h3>
                </div>
                <div class="box-body">
                    <table class="table table-hover" id="dominions-table">
                        <colgroup>
                            <col>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="200">
                            <col width="50">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th>Realm</th>
                                <th class="text-center">OP</th>
                                <th class="text-center">DP</th>
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                                <th class="text-center">Last Op</th>
                                <th class="text-center">Ops</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($targetDominions as $dominion)
                                <tr>
                                    <td>
                                        <a href="{{ route('dominion.op-center.show', $dominion) }}">{{ $dominion->name }}</a>
                                    </td>
                                    <td data-search="realm:{{ $dominion->realm->number }}">
                                        <a href="{{ route('dominion.realm', $dominion->realm->number) }}">{{ $dominion->realm->name }} (#{{ $dominion->realm->number }})</a>
                                        {{-- todo: highlight clicked dominion? --}}
                                    </td>
                                    <td class="text-center" data-search="">
                                        ?
                                    </td>
                                    <td class="text-center" data-search="">
                                        ?
                                    </td>
                                    <td class="text-center" data-search="">
                                        ?
                                    </td>
                                    <td class="text-center" data-search="">
                                        ?
                                    </td>
                                    <td class="text-center" data-search="" data-order="$date todo">
                                        x minutes ago
                                    </td>
                                    <td class="text-center" data-search="" data-order="2 todo">
                                        2/5
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Whenever you or someone else in your realm performs an information gathering spy or magic operation, the information you gather is posted here.</p>
                    <p>Through this page, you can help one another find targets and scout threats to one another.</p>
                    <p>You are only able to see dominions that are within your range.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#dominions-table').DataTable();
        })(jQuery);
    </script>
@endpush
