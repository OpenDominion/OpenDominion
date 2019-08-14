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
                            {{--
                            <col width="100">
                            <col width="100">
                            --}}
                            <col width="100">
                            <col width="100">
                            <col width="160">
                            <col width="130">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th>Realm</th>
                                <th class="text-center">Race</th>
                                {{--
                                <th class="text-center">OP</th>
                                <th class="text-center">DP</th>
                                --}}
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                                <th class="text-center">Last Op</th>
                                <th class="text-center">Recent Ops</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($latestInfoOps as $targetDominionOps)
                                @php $lastInfoOp = $targetDominionOps->first(); @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('dominion.op-center.show', $lastInfoOp->targetDominion) }}">{{ $lastInfoOp->targetDominion->name }}</a>
                                        @if ($lastInfoOp->isInvalid())
                                            <span class="label label-danger">Invalid</span>
                                        @elseif ($lastInfoOp->isStale())
                                            <span class="label label-warning">Stale</span>
                                        @endif
                                    </td>
                                    <td data-search="realm:{{ $lastInfoOp->targetDominion->realm->number }}">
                                        <a href="{{ route('dominion.realm', $lastInfoOp->targetDominion->realm->number) }}">{{ $lastInfoOp->targetDominion->realm->name }} (#{{ $lastInfoOp->targetDominion->realm->number }})</a>
                                        {{-- todo: highlight clicked dominion in realm page? --}}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $lastInfoOp->targetDominion->race->name }}">
                                        {{ $lastInfoOp->targetDominion->race->name }}
                                    </td>
                                    {{--
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getOffensivePower($selectedDominion->realm, $lastInfoOp->targetDominion) }}">
                                        {{ $infoOpService->getOffensivePowerString($selectedDominion->realm, $lastInfoOp->targetDominion) }}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getDefensivePower($selectedDominion->realm, $lastInfoOp->targetDominion) }}">
                                        {{ $infoOpService->getDefensivePowerString($selectedDominion->realm, $lastInfoOp->targetDominion) }}
                                    </td>
                                    --}}
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getLand($targetDominionOps) }}">
                                        {{ $infoOpService->getLandString($targetDominionOps) }}
                                        <br>
                                        <span class="small {{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $lastInfoOp->targetDominion) }}">
                                            {{ number_format($rangeCalculator->getDominionRange($selectedDominion, $lastInfoOp->targetDominion), 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getNetworth($targetDominionOps) }}">
                                        {{ $infoOpService->getNetworthString($targetDominionOps) }}
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $lastInfoOp->created_at->getTimestamp() }}">
                                        {{ $infoOpService->getInfoOpName($lastInfoOp) }}
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
                                            {{ $lastInfoOp->created_at }}
                                        </span>
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $infoOpService->getNumberOfActiveInfoOps($targetDominionOps) }}">
                                        {{ $infoOpService->getNumberOfActiveInfoOps($targetDominionOps) }}/{{ $infoOpService->getMaxInfoOps() }}
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
                    <table class="table table-hover" id="clairvoyance-table">
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
                            @foreach ($clairvoyances as $clairvoyance)
                                <tr>
                                    <td data-order="{{ $clairvoyance->targetRealm->number }}">
                                        <a href="{{ route('dominion.op-center.clairvoyance', $clairvoyance->targetRealm->number) }}">{{ $clairvoyance->targetRealm->name }} (#{{ $clairvoyance->targetRealm->number }})</a>
                                    </td>
                                    <td data-order="{{ $clairvoyance->targetDominion->name }}">
                                        <a href="{{ route('dominion.op-center.show', $clairvoyance->targetDominion->id) }}">{{ $clairvoyance->targetDominion->name }}</a>
                                    </td>
                                    <td class="text-center" data-search="" data-order="{{ $clairvoyance->created_at->getTimestamp() }}">
                                        Clairvoyance by
                                        @if ($clairvoyance->sourceDominion->id === $selectedDominion->id)
                                            <strong>
                                                {{ $selectedDominion->name }}
                                            </strong>
                                        @else
                                            {{ $clairvoyance->sourceDominion->name }}
                                        @endif
                                        <br>
                                        <span class="small">
                                            {{ $clairvoyance->created_at->diffForHumans() }}
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
                order: [[5, 'desc']],
            });
            $('#clairvoyance-table').DataTable({
                order: [[2, 'desc']],
            });
        })(jQuery);
    </script>
@endpush
