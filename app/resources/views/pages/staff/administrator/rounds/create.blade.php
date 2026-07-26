@extends('layouts.staff')

@section('page-header', 'Create Round')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">Create New Round</span>
        </div>
        <form action="{{ route('staff.administrator.rounds.create') }}" method="POST">
            @csrf
            @include('pages.staff.administrator.rounds.partials.form', ['round' => null])
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Create Round
                </button>
                <a href="{{ route('staff.administrator.rounds.index') }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
