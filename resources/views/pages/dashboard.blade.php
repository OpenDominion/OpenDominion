@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <p>Welcome back, <b>{{ Auth::user()->display_name }}</b>.</p>
        </div>
    </div>
@endsection
