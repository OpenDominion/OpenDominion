@extends('layouts.staff')

@section('page-header', 'Users')

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">Users</h3>
        </div>
        <div class="box-body">
            <table class="table table-hover" id="users-table">
                <colgroup>
                    <col width="50">
                    <col>
                    <col width="200">
                    <col width="200">
                    <col width="50">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Display Name</th>
                        <th class="text-center">Last Online</th>
                        <th class="text-center">Registered</th>
                        <th class="text-center">Activated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="text-center" data-search="">{{ $user->id }}</td>
                            <td>
                                <a href="{{ route('staff.administrator.users.show', $user) }}">{{ $user->display_name }}</a>
                            </td>
                            @if ($user->isOnline())
                                <td class="text-center" data-order="{{ $user->last_online->getTimestamp() }}" data-search="">Online</td>
                            @elseif ($user->last_online === null)
                                <td class="text-center" data-order="0" data-search="">Never</td>
                            @else
                                <td class="text-center" data-order="{{ $user->last_online->getTimestamp() }}" data-search="">
                                    <span title="{{ $user->last_online }}">{{ $user->last_online->diffForHumans() }}</span>
                                </td>
                            @endif
                            <td class="text-center" data-order="{{ $user->created_at->getTimestamp() }}" data-search="">
                                <span title="{{ $user->created_at }}">{{ $user->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="text-center" data-search="">{{ $user->activated ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables/css/dataTables.bootstrap.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/jquery.dataTables.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/vendor/datatables/js/dataTables.bootstrap.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#users-table').DataTable();
        })(jQuery);
    </script>
@endpush
