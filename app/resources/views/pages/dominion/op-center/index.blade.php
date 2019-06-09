@extends('layouts.master')

@section('page-header', 'Op Center')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">Op Center</h3>
                </div>
                <div class="box-body table-responsive">
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
                                @php
                                    $lastInfoOp = $infoOpService->getLastInfoOp($selectedDominion->realm, $dominion);
                                @endphp
                                @if ($lastInfoOp->isInvalid())
                                    @continue
                                @endif
                                <tr>
                                    <td>
                                        <a href="{{ route('dominion.op-center.show', $dominion) }}">{{ $dominion->name }}</a>
                                        @if ($lastInfoOp->isStale())
                                            <span class="label label-warning">Stale</span>
                                        @endif
                                    </td>
                                    <td data-search="realm:{{ $dominion->realm->number }}">
                                        <a href="{{ route('dominion.realm', $dominion->realm->number) }}">{{ $dominion->realm->name }} (#{{ $dominion->realm->number }})</a>
                                        {{-- todo: highlight clicked dominion in realm page? --}}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getOffensivePower($selectedDominion->realm, $dominion) }}">
                                        {{ $infoOpService->getOffensivePowerString($selectedDominion->realm, $dominion) }}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getDefensivePower($selectedDominion->realm, $dominion) }}">
                                        {{ $infoOpService->getDefensivePowerString($selectedDominion->realm, $dominion) }}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getLand($selectedDominion->realm, $dominion) }}">
                                        {{ $infoOpService->getLandString($selectedDominion->realm, $dominion) }}
                                        <br>
                                        <span class="small {{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $dominion) }}">
                                            {{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getNetworth($selectedDominion->realm, $dominion) }}">
                                        {{ $infoOpService->getNetworthString($selectedDominion->realm, $dominion) }}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $lastInfoOp->updated_at->getTimestamp() }}">
                                        {{ $infoOpService->getLastInfoOpName($selectedDominion->realm, $dominion) }}
                                        by
                                        @if ($lastInfoOp->sourceDominion->id === $selectedDominion->id)
                                            <strong>
                                                {{ $selectedDominion->name }}
                                            </strong>
                                        @else
                                            {{ $lastInfoOp->sourceDominion->name }}
                                        @endif
                                        <br>
                                        <span class="small">
                                            {{ $lastInfoOp->updated_at }}
                                        </span>
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getNumberOfActiveInfoOps($selectedDominion->realm, $dominion) }}">
                                        {{ $infoOpService->getNumberOfActiveInfoOps($selectedDominion->realm, $dominion) }}/{{ $infoOpService->getMaxInfoOps() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">Clairvoyance Realms</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-hover" id="dominions-table">
                        <colgroup>
                            <col>
                            <col>
                            <col width="200">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Realm</th>
                                <th>Target</th>
                                <th class="text-center">Taken</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clairvoyanceRealms as $realm)
                                @php
                                    $lastInfoOp = $infoOpService->getLastClairvoyance($selectedDominion->realm, $realm);
                                @endphp
                                @if ($lastInfoOp->isInvalid())
                                    @continue
                                @endif
                                <tr>
                                    <td data-order="{{ $realm->number }}">
                                        <a href="{{ route('dominion.op-center.clairvoyance', $realm->number) }}">{{ $realm->name }} (#{{ $realm->number }})</a>
                                    </td>
                                    <td data-order="{{ $lastInfoOp->targetDominion->name }}">
                                        <a href="{{ route('dominion.op-center.show', $lastInfoOp->targetDominion) }}">{{ $lastInfoOp->targetDominion->name }}</a>
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $lastInfoOp->updated_at->getTimestamp() }}">
                                        Clairvoyance by
                                        @if ($lastInfoOp->sourceDominion->id === $selectedDominion->id)
                                            <strong>
                                                {{ $selectedDominion->name }}
                                            </strong>
                                        @else
                                            {{ $lastInfoOp->sourceDominion->name }}
                                        @endif
                                        <br>
                                        <span class="small">
                                            {{ $lastInfoOp->updated_at->diffForHumans() }}
                                        </span>
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
                    <p>Whenever you or someone else in your realm performs an information gathering espionage operation or magic spell, the information gathered is posted in the Op Center.</p>
                    <p>Through this page, you can help one another find targets and scout threats to one another.</p>
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
            $('#dominions-table').DataTable({
                order: [[6, 'desc']],
            });
        })(jQuery);
    </script>
@endpush
