@extends('layouts.staff')

@section('page-header', 'Create Hero Tournament')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">Create New Hero Tournament for {{ $round->name }}</span>
        </div>
        <form action="{{ route('staff.administrator.hero-tournaments.create') }}" method="POST">
            @csrf
            <input type="hidden" name="round_id" value="{{ $round->id }}">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name">Tournament Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', 'The Grand Tournament') }}" required>
                            @if ($errors->has('name'))
                                <span class="form-text text-red">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="start_day">Start Day of Round *</label>
                            <input type="number" name="start_day" id="start_day" class="form-control" value="{{ old('start_day') }}" min="1" required>
                            <small class="text-muted">Registration closes and matchups begin on this day</small>
                            @if ($errors->has('start_day'))
                                <span class="form-text text-red">{{ $errors->first('start_day') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Create Tournament
                </button>
                <a href="{{ route('staff.administrator.hero-tournaments.index', ['round' => $round->id]) }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
