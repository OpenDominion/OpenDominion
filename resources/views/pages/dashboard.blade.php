@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <p>Welcome back, <b>{{ Auth::user()->display_name }}</b>.</p>

            @if ($dominions->isEmpty())
                <p>You currently have no active dominions.</p>
            @endif

            @if ($rounds->isEmpty())
                <p>There are currently no active rounds.</p>
            @else
                <p>Active rounds: {{ $rounds->count() }}</p>
                <ul>
                    @foreach ($rounds->all() as $round)
                        <li>{{ $round->name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
