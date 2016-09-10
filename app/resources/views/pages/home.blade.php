@extends('layouts.master')

@section('page-header', 'Welcome to OpenDominion')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <p>OpenDominion is a free online text-based strategy game in a medieval fantasy setting. You control a kingdom called 'dominion', complete with resources, buildings, land and units. You must work together with allied dominions in your realm to be the wealthiest and most powerful realm in the current round!</p>
            <p>To start playing, <a href="{{ route('auth.register') }}">register</a> an account and sign up for a round after registration. If you already have an account, <a href="{{ route('auth.login') }}">login</a> instead.</p>
            <p>OpenDominion is based on Dominion from Kamikaze Games, which ran from about 2000 to 2012 until stopping indefinitely.</p>
            <p>OpenDominion is also open source software and can be found on <a href="https://github.com/WaveHack/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>.</p>
        </div>
    </div>
@endsection
