@extends('layouts.master')

@section('page-header', 'Register')

@section('content')
    <div class="row">
        <div class="col-sm-6 ocl-md-5 col-lg-4">
            <form action="{{ route('auth.register') }}" method="post" role="form">
                {{ csrf_field() }}
                <fieldset>
                    <div class="form-group">
                        <input type="text" class="form-control" name="display_name" placeholder="Display Name" autofocus>
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control" name="email" placeholder="Email">
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password" placeholder="Password">
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control" name="password_confirmation" placeholder="Password (Confirm)">
                        <p class="help-block">Note that unlike vanilla Dominion, you will register for a round with dominion details later. Your user account will be persistent across rounds and Dominions.</p>
                    </div>
                    <button type="submit" class="btn btn-lg btn-primary btn-block">Register</button>
                </fieldset>
            </form>
        </div>
    </div>
@endsection
