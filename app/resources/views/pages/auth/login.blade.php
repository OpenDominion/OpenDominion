@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-sm-offset-2">

            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Login</h3>
                </div>
                <form action="{{ route('auth.login') }}" method="post" class="form-horizontal" role="form">
                    {{ csrf_field() }}

                    <div class="box-body">

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email">
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password" class="col-sm-3 control-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Email">
                            </div>
                        </div>

                        <!-- Remember me -->
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox"> Remember me
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Button -->

                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Login</button>
                        <div class="pull-right">
                            Don't have an account? <a href="{{ route('auth.register') }}">Register</a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection
