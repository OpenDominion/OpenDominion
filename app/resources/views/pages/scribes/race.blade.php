@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    @include('partials.scribes.race-box', ['race' => $race])
@endsection
