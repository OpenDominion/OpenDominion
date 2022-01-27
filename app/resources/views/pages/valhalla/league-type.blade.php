@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $ranking['name'] }} in {{ $league->description }}</h3>
        </div>

        @if (!$standings->isEmpty())
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped">
                    <colgroup>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>User</th>
                            <th>Total {{ $ranking['stat_label'] }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($standings as $standing)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>
                                    <a href="{{ route('valhalla.user', $standing['user_id']) }}">
                                        {{ $standing['display_name'] }}
                                    </a>
                                </td>
                                <td>{{ number_format($standing['value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="box-body">
                <p>No records found.</p>
            </div>
        @endif
    </div>
@endsection
