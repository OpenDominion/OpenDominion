@extends('layouts.staff')

@section('page-header', 'Edit Round')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">Edit {{ $round->name }}</span>
        </div>
        <form action="{{ route('staff.administrator.rounds.edit', $round) }}" method="POST">
            @csrf
            @include('pages.staff.administrator.rounds.partials.form', ['round' => $round, 'defaults' => null])
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Save Changes
                </button>
                <a href="{{ route('staff.administrator.rounds.show', $round) }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
