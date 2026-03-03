@extends('layouts.staff')

@section('page-header', 'Staff')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Staff Members</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-center">Administrator</th>
                        <th class="text-center">Developer</th>
                        <th class="text-center">Moderator</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($staff as $user)
                        <tr>
                            <td>{{ $user->display_name }}</td>
                            <td class="text-center">
                                @if ($user->hasRole('Administrator'))
                                    <i class="fa fa-check text-green"></i>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($user->hasRole('Developer'))
                                    <i class="fa fa-check text-green"></i>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($user->hasRole('Moderator'))
                                    <i class="fa fa-check text-green"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
