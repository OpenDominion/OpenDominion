@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">User Search: {{ $search }} ({{ $users->count() == 50 ? 'limited to 50' : $users->count() }} results)</h3>
        </div>

        @if (!$users->isEmpty())
            <div class="box-body table-responsive no-padding">
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
            <div class="box-body">
                <p>No records found.</p>
            </div>
        @endif
    </div>
@endsection
