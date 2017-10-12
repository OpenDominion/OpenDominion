@extends('layouts.master')

@section('page-header', 'Settings')

@section('content')
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

                        {{--<div class="col-md-6">--}}

                            <table class="table">
                                <colgroup>
                                    <col>
                                    <col width="100">
                                    <col width="100">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Notification</th>
                                        <th class="text-center">In-game</th>
                                        <th class="text-center">Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    // todo: cleanup this temp stuff
                                    $notifications = [
                                        'Land exploration finishes',
                                        'Building construction completes',
                                        'Military training finishes',
                                        'Units return from battle',
                                        'Dominion being invaded',
                                        'Op Center information gathered',
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

                        {{--</div>--}}


                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Update Notifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
