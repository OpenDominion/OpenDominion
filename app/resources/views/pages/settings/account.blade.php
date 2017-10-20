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
                    <h3 class="box-title">Account Settings</h3>
                </div>
                <form action="" method="post" role="form">
                    <div class="box-body">

                        <div class="row">
                            <div class="col-md-6">

                                {{--<h2 class="page-header">Basic Information</h2>--}}

                                {{-- Display Name --}}
                                <div class="form-group">
                                    <label>Display Name</label>
                                    <input type="text" class="form-control" value="{{ $user->display_name }}" readonly>
                                    <p class="help-block">Your display name cannot be changed.</p>
                                </div>

                                {{-- Email --}}
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                                    <p class="help-block">Your email address cannot be changed at the moment.</p>
                                    {{--<p class="help-block">Your email address will be changed upon </p>--}}
                                    {{--<p class="form-control-static">{{ $user->display_name }}</p>--}}
                                </div>

                                {{--<h2 class="page-header">Password</h2>--}}

                                {{--<p>Only fill in this section if you want to change your password.</p>--}}

                                {{--<div class="form-group">--}}
                                    {{--<label for="">Old password</label>--}}
                                    {{--<input type="password" class="form-control" placeholder="Old password">--}}
                                {{--</div>--}}

                                {{--<div class="form-group">--}}
                                    {{--<label for="">New password</label>--}}
                                    {{--<input type="password" class="form-control" placeholder="New password">--}}
                                {{--</div>--}}

                                {{--<div class="form-group">--}}
                                    {{--<label for="">Confirm new password</label>--}}
                                    {{--<input type="password" class="form-control" placeholder="Confirm new password">--}}
                                {{--</div>--}}

                            </div>
                            <div class="col-md-3 col-md-offset-3">

                                {{-- Avatar --}}
                                <div class="form-group">
                                    <label for="avatar">Avatar</label>
                                    <div style="margin-bottom: 10px;">
                                        <img src="{{ Gravatar::src($user->email, 200) }}" class="img-responsive" alt="{{ $user->display_name }}">
                                    </div>
                                    <button type="button" class="btn btn-default btn-block">Upload new avatar</button>
                                    {{--<input type="file" name="avatar">--}}
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
