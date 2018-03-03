@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-sm-offset-3">

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Reset Password</h3>
                </div>
                <form action="{{ route('auth.password.email') }}" method="post" class="form-horizontal" role="form">
                    {{ csrf_field() }}

                    <div class="box-body">

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required autofocus>
                            </div>
                        </div>

                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Send Password Reset Link</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection
