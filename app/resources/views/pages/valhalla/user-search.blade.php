@extends('layouts.topnav')

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">User Search: {{ $search }} ({{ $users->count() == 50 ? 'limited to 50' : $users->count() }} results)</h3>
        </div>

        @if (!$users->isEmpty())
            <div class="card-body table-responsive no-padding">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('valhalla.user', $user->id) }}">
                                        {{ $user->display_name }}
                                    </a>
                                </td>
                                <td>{{ $user->created_at }}</td>
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
