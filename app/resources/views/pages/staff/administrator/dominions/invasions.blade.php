@extends('layouts.staff')

@section('page-header', 'Anti-Cheat')

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">
                Invasions - {{ $round->name }}
            </h3>
            <select id="round-select" class="form-control pull-right">
                @foreach ($rounds as $roundOption)
                    <option value="{{ $roundOption->id }}" {{ $roundOption->id == $round->id ? 'selected' : null }}>
                        {{ $roundOption->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-hover" id="dominions-table">
                <colgroup>
                    <col>
                    <col>
                    <col width="100">
                    <col width="200">
                </colgroup>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Target</th>
                        <th class="text-center">Ops</th>
                        <th class="text-center">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invasions as $invasion)
                        <tr>
                            <td>{{ $invasion->source_name }}</td>
                            <td>{{ $invasion->target_name }}</td>
                            <td class="text-center" data-search="">{{ $invasion->ops_count }}</td>
                            <td class="text-center" data-order="{{ $invasion->created_at->getTimestamp() }}" data-search="">
                                <span title="{{ $invasion->created_at }}">{{ $invasion->created_at->diffForHumans() }}</span>
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
                window.location.href = "{!! route('staff.administrator.invasions') !!}/?round=" + selectedRound;
            });
            $('#round-select + .select2-container').addClass('pull-right');
        })(jQuery);
    </script>
@endpush
