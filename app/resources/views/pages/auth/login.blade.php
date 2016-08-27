@extends('layouts.master')

@section('page-header', 'Login')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-5 col-lg-4">
            <form action="{{ route('auth.login') }}" method="post" role="form">
                {{ csrf_field() }}
                <fieldset>
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Email" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="Password">
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="remember">Remember Me
                        </label>
                    </div>
                    <button type="submit" class="btn btn-lg btn-success btn-block">Login</button>
                </fieldset>
            </form>
        </div>
    </div>
@endsection
