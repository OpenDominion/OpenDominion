@extends('layouts.master')

@section('page-header', 'Welcome to OpenDominion')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <p>OpenDominion is a free online text-based strategy game based on Dominion, a defunct online text-based strategy game from Kamikaze Games which ran from about 2000 to 2012.</p>
            <p>To start playing, <a href="{{ route('auth.register') }}">register</a> an account and sign up for a round after registration. If you already have an account, <a href="{{ route('auth.login') }}">login</a> instead.</p>
            <p>OpenDominion is open source software and can be found on <a href="https://github.com/WaveHack/OpenDominion" target="_blank">GitHub <i class="fa fa-external-link"></i></a>.</p>
        </div>
    </div>
@endsection
