@extends('layouts.staff')

@section('page-header', 'Anti-Cheat')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Crosslogs - {{ $round->name }}
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
                    <col>
                    <col>
                    <col width="100">
                    <col width="100">
                    <col width="100">
                </colgroup>
                <thead>
                    <tr>
                        <th>Dominions</th>
                        <th>Users</th>
                        <th>Realms</th>
                        <th class="text-center">Count</th>
                        <th class="text-center">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($crosslogs as $crosslog)
                        <tr>
                            <td>{{ $crosslog->dominions }}</td>
                            <td>{{ $crosslog->users }}</td>
                            <td>{{ $crosslog->realms }}</td>
                            <td class="text-center" data-search="">{{ $crosslog->count }}</td>
                            <td class="text-center">{{ $crosslog->ip }}</td>
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
                window.location.href = "{!! route('staff.administrator.crosslogs') !!}/?round=" + selectedRound;
            });
            $('#round-select + .select2-container').addClass('float-end');
        })(jQuery);
    </script>
@endpush
