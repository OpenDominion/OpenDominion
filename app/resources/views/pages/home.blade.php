@extends('layouts.topnav')

@section('content')
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Welcome to OpenDominion!</h3>
        </div>
        <div class="box-body">
            <p>OpenDominion is a free online text-based strategy game in a medieval fantasy setting. You control a kingdom called a 'dominion', along with its resources, buildings, land and units. You are placed in a realm with up to 14 other dominions and you must work together to make your realm the wealthiest and most powerful in the current round!</p>

            <p>To start playing, <a href="{{ route('auth.register') }}">register</a> an account and sign up for a round after registration. If you already have an account, <a href="{{ route('auth.login') }}">login</a> instead.</p>

            <p>OpenDominion is based on Dominion from Kamikaze Games. Dominion ran from about 2000 to 2012 until stopping indefinitely.</p>

            <p>OpenDominion is open source software and can be found on <a href="https://github.com/WaveHack/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>.</p>
        </div>
    </div>
@endsection
