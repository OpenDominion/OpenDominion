@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-6 offset-sm-3">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Reset Password</h3>
                </div>
                <form action="{{ route('auth.password.email') }}" method="post" class="form-horizontal" role="form">
                    @csrf

                    <div class="card-body">

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required autofocus>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Send Password Reset Link</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection
