@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <pre>{{ print_r(json_decode($dominion), true) }}</pre>
@endsection
