@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-6 offset-sm-3">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Reset Password</h3>
                </div>
                <form action="{{ route('auth.password.request') }}" method="post" class="form-horizontal" role="form">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="card-body">

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{ $email or old('email') }}" required autofocus>
                            </div>
                        </div>

                        {{-- Password --}}
                        <div class="form-group">
                            <label for="password" class="col-sm-3 control-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>

                        {{-- Password (confirm) --}}
                        <div class="form-group">
                            <label for="password_confirmation" class="col-sm-3 control-label">Password (confirm)</label>
                            <div class="col-sm-9">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Password (confirm)" required>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
@endsection
