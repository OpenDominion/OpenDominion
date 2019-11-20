@extends('layouts.master')

@section('page-header', 'Search Dominions')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-search"></i> Search Dominions</h3>
                </div>
                <div class="box-body table-responsive" id="dominion-search">
                    <div class="row no-margin">
                        <div class="col-sm-6 col-md-4 form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Race:</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="race">
                                        <option value="">All</option>
                                        @foreach ($dominions->pluck('race.name')->sort()->unique() as $race)
                                            <option value="{{ $race }}">{{ $race }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Limit:</label>
                                <div class="col-sm-6">
                                    <select class="form-control" name="range">
                                        <option value="true">No Limit</option>
                                        <option value="">My Range</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Land Min:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="landMin" class="form-control input-sm" min="0" placeholder="0" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Land Max:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="landMax" class="form-control input-sm" placeholder="--" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12 text-right">
                                    <button class="btn btn-default search-range" data-min="{{ ceil($landCalculator->getTotalLand($selectedDominion) * 0.40) }}" data-max="{{ floor($landCalculator->getTotalLand($selectedDominion) / 0.40) }}">40%</button>
                                    <button class="btn btn-success search-range" data-min="{{ ceil($landCalculator->getTotalLand($selectedDominion) * 0.60) }}" data-max="{{ floor($landCalculator->getTotalLand($selectedDominion) / 0.60) }}">60%</button>
                                    <button class="btn btn-warning search-range" data-min="{{ ceil($landCalculator->getTotalLand($selectedDominion) * 0.75) }}" data-max="{{ floor($landCalculator->getTotalLand($selectedDominion) / 0.75) }}">75%</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4 form-horizontal">
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Networth Min:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="networthMin" class="form-control input-sm" min="0" placeholder="0" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 control-label text-right">Networth Max:</label>
                                <div class="col-sm-6">
                                    <input type="number" name="networthMax" class="form-control input-sm" placeholder="--" />
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-6 col-sm-offset-6">
                                    <button id="dominion-search" class="btn btn-block btn-primary">Search</button>
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
                                <th class="text-center hidden">My Range</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($selectedDominion->round->hasStarted())
                                @foreach ($dominions as $dominion)
                                    <tr>
                                        <td data-search="{{ $dominion->name }}">
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
                                            @if ($rangeCalculator->isInRange($selectedDominion, $dominion))
                                                true
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
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
                    <p>Advanced search for locating dominions in other realms.</p>
                    <p>By default, it is limited to targets that you can perform actions against due to range restrictions.</p>
                    @if (!$selectedDominion->round->hasStarted())
                        <p>The current round has not started. No dominions will be listed.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <style>
        #dominion-search #dominions-table_filter { display: none !important; }
    </style>
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

                var range = $('select[name=range]').val();
                if (range && data[5] != "true") return false;

                return true;
            }
        );
        (function ($) {
            var table = $('#dominions-table').DataTable({
                order: [[3, 'desc']],
                paging: false,
            });
            $('#dominion-search').click(function() {
                table.draw();
            });
            $('.search-range').click(function() {
                $('input[name=landMin]').val($(this).data('min'));
                $('input[name=landMax]').val($(this).data('max'));
            })
        })(jQuery);
    </script>
@endpush
