@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Register</h3>
                </div>
                <form action="{{ route('auth.register') }}" method="post" class="form-horizontal" role="form">
                    {{ csrf_field() }}

                    <div class="box-body">

                        <!-- Display Name -->
                        <div class="form-group">
                            <label for="display_name" class="col-sm-3 control-label">Display Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="display_name" id="display_name" class="form-control" placeholder="Display Name" value="{{ old('display_name') }}">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{ old('email') }}">
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password" class="col-sm-3 control-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Password">
                            </div>
                        </div>

                        <!-- Password Confirm -->
                        <div class="form-group">
                            <label for="password_confirmation" class="col-sm-3 control-label">Password (confirm)</label>
                            <div class="col-sm-9">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Password (confirm)">
                            </div>
                        </div>

                        <p>Note that unlike vanilla Dominion, you will register for a round with dominion details later. Your user account will be persistent across rounds and Dominions.</p>

                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Register</button>
                        <div class="pull-right">
                            Already have an account? <a href="{{ route('auth.login') }}">Login</a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection
