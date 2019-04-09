@extends('layouts.topnav')

{{-- todo: refactor this --}}

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ ucwords(str_replace('-', ' ', $type)) }} in round {{ number_format($round->number) }}: {{ $round->name }}</h3>
        </div>

        @if (!empty($data))
            <div class="box-body table-responsive no-padding">
                @php($headersPrinted = false)
                <table class="table table-striped">
                    <colgroup>
                        @foreach (array_keys($data[0]) as $column)
                            <col{!! isset($headers[$column]['width']) ? (' width="' . $headers[$column]['width'] . '"') : null !!}>
                        @endforeach
                    </colgroup>
                    @foreach ($data as $row)
                        @if (!$headersPrinted)
                            <thead>
                                <tr>
                                    @foreach (array_keys($row) as $column)
                                        <th{!! (isset($headers[$column]['align-center']) && $headers[$column]['align-center']) ? ' class="text-center"' : null !!}>
                                            {{ ucwords($column) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @php($headersPrinted = true)
                        @endif

                        <tr>
                            @foreach ($row as $column => $value)
                                <td{!! (isset($headers[$column]['align-center']) && $headers[$column]['align-center']) ? ' class="text-center"' : null !!}>
                                    {{ $value }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        @else
            <div class="box-body">No records found.</div>
        @endif
    </div>
@endsection
