@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    @foreach ($races as $race)
        @include('partials.scribes.race-box', ['race' => $race])
    @endforeach
@endsection