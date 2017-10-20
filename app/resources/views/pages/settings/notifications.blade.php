@extends('layouts.master')

@section('page-header', 'Settings')

@section('content')
    @php
        $user = Auth::user();
    @endphp
    <div class="row">
        <div class="col-lg-3">
            @include('partials.settings-list-group')
        </div>

        <div class="col-lg-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Notification Settings</h3>
                </div>
                <form action="" method="post" role="form">
                    <div class="box-body">

                        <div class="row">
                            <div class="col-md-6">

                                <table class="table">
                                    <colgroup>
                                        <col>
                                        <col width="100">
                                        <col width="100">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Hourly Events</th>
                                            <th class="text-center">In-game</th>
                                            <th class="text-center">Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        // todo: cleanup this temp stuff
                                        $notifications = [
                                            'Land exploration completes',
                                            'Building construction completes',
                                            'Military training completes',
                                            'Units returned from battle',
                                            'Beneficial magic effect dissipates',
                                            'Starvation occurred',
                                            //'Dominion being invaded',
                                            //'Op Center information gathered',
                                        ];

                                        foreach ($notifications as $notification) {
                                            echo '<tr>';
                                            echo "<td>{$notification}</td>";
                                            echo '<td class="text-center"><input type="checkbox"></td>';
                                            echo '<td class="text-center"><input type="checkbox"></td>';
                                            echo '</tr>';
                                        }

                                        @endphp
    {{--                                    <tr>
                                            <td>Building constructed</td>
                                            <td class="text-center">
                                                <input type="checkbox">
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox">
                                            </td>
                                        </tr>--}}
                                    </tbody>
                                </table>

                            </div>
                        </div>


                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Update Notifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
