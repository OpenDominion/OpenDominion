@extends('layouts.staff')

@section('page-header', "User: {$user->display_name}")

@section('content')
    <pre>{{ print_r(json_decode($user), true) }}</pre>
@endsection
