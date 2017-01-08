@extends('layouts.master')

@section('page-header', 'Production Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    {{ $productionCalculator->getPlatinumProduction() }}
@endsection
