@extends('layouts.staff')

@section('page-header', 'Dominions')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Dominions - {{ $round->name }}
            </h3>
            <select id="round-select" class="form-control float-end">
                @foreach ($rounds as $roundOption)
                    <option value="{{ $roundOption->id }}" {{ $roundOption->id == $round->id ? 'selected' : null }}>
                        {{ $roundOption->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover" id="dominions-table">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="200">
                    <col width="100">
                    <col width="100">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Name</th>
                        <th class="text-center">User</th>
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
                                <a href="{{ route('staff.administrator.dominions.show', $dominion) }}">{{ $dominion->name }}</a>
                            </td>
                            <td class="text-center">
                                @if ($dominion->user)
                                    <a href="{{ route('staff.administrator.users.show', $dominion->user) }}" data-bs-toggle="tooltip" title="{{ $dominion->user->display_name }}">
                                        Human Player
                                    </a>
                                @else
                                    Bot
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
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap5.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap5.js') }}"></script>
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
                window.location.href = "{!! route('staff.administrator.dominions.index') !!}/?round=" + selectedRound;
            });
            $('#round-select + .select2-container').addClass('float-end');
        })(jQuery);
    </script>
@endpush
