@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-6 offset-sm-3">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Register</h3>
                </div>
                <form action="{{ route('auth.register') }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    @honeypot

                    <div class="card-body">

                        <p>Unlike classic Dominion, you only need to register for a user account once.</p>
                        <p>Once you activate your user account, you can sign up for an active round and start playing. Your user account will be persistent across rounds and dominions.</p>

                        {{-- Display Name --}}
                        <div class="form-group">
                            <label for="display_name" class="col-sm-3 control-label">Display Name</label>
                            <div class="col-sm-9">
                                <input type="text" name="display_name" id="display_name" class="form-control" placeholder="Display Name" value="{{ old('display_name') }}" required autofocus>
                                <span class="form-text">
                                    Your display name will be shown on your public profile and in Valhalla (leaderboards).
                                </span>
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email" class="col-sm-3 control-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="{{ old('email') }}" required>
                                <span class="form-text">
                                    Please use a valid email address, since you need to validate your account before you can start playing.
                                </span>
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

                        {{-- Terms and Conditions --}}
                        <div class="form-group">
                            <div class="offset-sm-3 col-sm-9">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="agreement_rules" name="agreement_rules" />
                                        I will adhere to the rules described in the <a href="#" data-bs-toggle="modal" data-bs-target="#user-agreement">User Agreement</a>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Turnstile CAPTCHA --}}
                        @if (config('turnstile.enabled'))
                            <div class="form-group">
                                <div class="offset-sm-3 col-sm-9">
                                    <div class="cf-turnstile" data-sitekey="{{ config('turnstile.site_key') }}"></div>
                                </div>
                            </div>
                        @endif

                        <ul class="text-muted">
                            <li>We will not share your data with anyone.</li>
                            <li>We will only send game-related emails to your email address.</li>
                            <li>Anonymized analytical data will be collected through Google Analytics the sole purpose of improving the game. Feel free to exempt yourself by using something like adblock or Ghostery.</li>
                        </ul>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="register_submit" disabled>Register</button>
                        <div class="float-end">
                            Already have an account? <a href="{{ route('auth.login') }}">Login</a>
                        </div>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="user-agreement" tabindex="-1" role="dialog" aria-labelledby="user-agreement-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="user-agreement-label">User Agreement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('partials.user-agreement')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
            </div>
        </div>
    </div>
@endsection

@push('inline-scripts')
    @if(config('turnstile.enabled'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script type="text/javascript">
        (function ($) {
            $('#agreement_rules').change(function() {
                if ($(this).is(":checked")) {
                    $('#register_submit').prop('disabled', false);
                } else {
                    $('#register_submit').prop('disabled', true);
                }
            });
        })(jQuery);
    </script>
@endpush
