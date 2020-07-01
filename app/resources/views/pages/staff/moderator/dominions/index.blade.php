@extends('layouts.staff')

@section('page-header', 'Dominions')

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">Dominions - {{ $round->name }}</h3>
            <select id="round-select" class="form-control pull-right">
                @foreach ($rounds as $roundOption)
                    <option value="{{ $roundOption->id }}" {{ $roundOption->id == $round->id ? 'selected' : null }}>
                        {{ $roundOption->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="box-body">
            <table class="table table-hover" id="dominions-table">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="200">
                    <col width="200">
                    <col width="100">
                    <col width="100">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Name</th>
                        <th class="text-center">Round</th>
                        <th class="text-center">Bot</th>
                        <th class="text-center">Land</th>
                        <th class="text-center">Networth</th>
                        <th class="text-center">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dominions as $dominion)
                        @php
                        $land = $landCalculator->getTotalLand($dominion);
                        $networth = $networthCalculator->getDominionNetworth($dominion);
                        @endphp
                        <tr>
                            <td class="text-center" data-search="">{{ $dominion->id }}</td>
                            <td>
                                <a href="{{ route('staff.moderator.dominions.show', $dominion) }}">{{ $dominion->name }}</a>
                            </td>
                            <td class="text-center" data-search="round:{{ $dominion->round->number }}">
                                <a href="#">{{ $dominion->round->name }} (#{{ $dominion->round->number }})</a>
                            </td>
                            <td class="text-center">
                                @if ($dominion->user)
                                    No
                                @else
                                    Yes
                                @endif
                            </td>
                            <td class="text-center" data-order="{{ $land }}" data-search="">
                                {{ number_format($land) }}
                            </td>
                            <td class="text-center" data-order="{{ $networth }}" data-search="">
                                {{ number_format($networth) }}
                            </td>
                            <td class="text-center" data-order="{{ $dominion->created_at->getTimestamp() }}" data-search="">
                                <span title="{{ $dominion->created_at }}">{{ $dominion->created_at->diffForHumans() }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#dominions-table').DataTable({
                "dom": '<"top"fi<"clear">>rt<"bottom"ilp<"clear">>',
                'paging': false
            });

            $('#round-select').select2({ width: '225px' }).change(function() {
                var selectedRound = $(this).val();
                window.location.href = "{!! route('staff.moderator.dominions.index') !!}/?round=" + selectedRound;
            });
            $('#round-select + .select2-container').addClass('pull-right');
        })(jQuery);
    </script>
@endpush
