@extends('layouts.topnav')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">{{ $ranking['name'] }} in {{ $league->description }}</h3>
        </div>

        @if (!$standings->isEmpty())
            <div class="card-body table-responsive no-padding">
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
            <div class="card-body">
                <p>No records found.</p>
            </div>
        @endif
    </div>
@endsection
