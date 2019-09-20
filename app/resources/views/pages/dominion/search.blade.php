@extends('layouts.master')

@section('page-header', 'Search Dominions')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-search"></i> Search Dominions</h3>
                </div>
                <div class="box-body table-responsive">
                    <div class="row no-margin">
                        <div class="form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-right">Race:</label>
                                <div class="col-sm-2">
                                    <select class="form-control" name="race">
                                        <option value="">Any</option>
                                        @foreach ($dominions->pluck('race.name')->sort()->unique() as $race)
                                            <option value="{{ $race }}">{{ $race }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label text-right">Land Min:</label>
                                <div class="col-sm-2">
                                    <input type="text" name="landMin" class="form-control input-sm" placeholder="0" />
                                </div>
                                <label class="col-sm-2 control-label text-right">Networth Min:</label>
                                <div class="col-sm-2">
                                    <input type="text" name="networthMin" class="form-control input-sm" placeholder="0" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label text-right">Guard Status:</label>
                                <div class="col-sm-2">
                                    <select class="form-control" name="guard">
                                        <option value="">Any</option>
                                        <option value="No Guard">No Guard</option>
                                        <option value="Royal Guard">Royal Guard</option>
                                        <option value="Elite Guard">Elite Guard</option>
                                    </select>
                                </div>
                                <label class="col-sm-2 control-label text-right">Land Max:</label>
                                <div class="col-sm-2">
                                    <input type="text" name="landMax" class="form-control input-sm" placeholder="--" />
                                </div>
                                <label class="col-sm-2 control-label text-right">Networth Max:</label>
                                <div class="col-sm-2">
                                    <input type="text" name="networthMax" class="form-control input-sm" placeholder="--" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-hover" id="dominions-table">
                        <colgroup>
                            <col>
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100">
                            <col width="100" class="hidden">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th class="text-center">Realm</th>
                                <th class="text-center">Race</th>
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                                <th class="text-center hidden">Guard Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dominions as $dominion)
                                <tr>
                                    <td data-search="{{ $dominion->name }}" data-guard="<?php if ($guardMembershipService->isEliteGuardMember($dominion)) echo "eg"; elseif ($guardMembershipService->isRoyalGuardMember($dominion)) echo "rg"; else echo "ng"; ?>">
                                        @if ($protectionService->isUnderProtection($dominion))
                                            <i class="ra ra-shield ra-lg text-aqua" title="Under Protection"></i>
                                        @endif

                                        @if ($guardMembershipService->isEliteGuardMember($dominion))
                                            <i class="ra ra-heavy-shield ra-lg text-yellow" title="Elite Guard"></i>
                                        @elseif ($guardMembershipService->isRoyalGuardMember($dominion))
                                            <i class="ra ra-heavy-shield ra-lg text-green" title="Royal Guard"></i>
                                        @endif

                                        <a href="{{ route('dominion.op-center.show', $dominion) }}">{{ $dominion->name }}</a>
                                    </td>
                                    <td class="text-center">
                                        {{ $dominion->realm->number }}
                                    </td>
                                    <td class="text-center">
                                        {{ $dominion->race->name }}
                                    </td>
                                    <td class="text-center" data-order="{{ $landCalculator->getTotalLand($dominion) }}" data-search="{{ $landCalculator->getTotalLand($dominion) }}">
                                        {{ number_format($landCalculator->getTotalLand($dominion)) }}
                                    </td>
                                    <td class="text-center" data-order="{{ $networthCalculator->getDominionNetworth($dominion) }}" data-search="{{ $networthCalculator->getDominionNetworth($dominion) }}">
                                        {{ number_format($networthCalculator->getDominionNetworth($dominion)) }}
                                    </td>
                                    <td class="hidden">
                                        <?php if ($guardMembershipService->isEliteGuardMember($dominion)) echo "Elite Guard"; elseif ($guardMembershipService->isRoyalGuardMember($dominion)) echo "Royal Guard"; else echo "No Guard"; ?>
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
                    box body
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
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var race = $('select[name=race]').val();
                if (race && race != data[2]) return false;

                var landMin = parseInt($('input[name=landMin]').val());
                var landMax = parseInt($('input[name=landMax]').val());
                var land = parseFloat(data[3]) || 0;
        
                if (!(isNaN(landMin) && isNaN(landMax)) &&
                    !(isNaN(landMin) && land <= landMax) &&
                    !(landMin <= land && isNaN(landMax)) &&
                    !(landMin <= land && land <= landMax))
                {
                    return false;
                }

                var nwMin = parseInt($('input[name=networthMin]').val());
                var nwMax = parseInt($('input[name=networthMax]').val());
                var nw = parseFloat(data[4]) || 0;
        
                if (!(isNaN(nwMin) && isNaN(nwMax)) &&
                    !(isNaN(nwMin) && nw <= nwMax) &&
                    !(nwMin <= nw && isNaN(nwMax)) &&
                    !(nwMin <= nw && nw <= nwMax))
                {
                    return false;
                }

                var guard = $('select[name=guard]').val();
                if (guard && guard != data[5]) return false;

                return true;
            }
        );
        (function ($) {
            var table = $('#dominions-table').DataTable({
                order: [[3, 'desc']],
                paging: false,
            });
            $('input[name=landMin], input[name=landMax]').keyup(function() {
                table.draw();
            });
            $('input[name=networthMin], input[name=networthMax]').keyup(function() {
                table.draw();
            });
            $('select[name=race], select[name=guard]').change(function() {
                table.draw();
            });
        })(jQuery);
    </script>
@endpush
