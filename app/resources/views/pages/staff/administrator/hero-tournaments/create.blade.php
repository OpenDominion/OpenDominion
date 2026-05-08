@extends('layouts.staff')

@section('page-header', 'Create Hero Tournament')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Create New Hero Tournament for {{ $round->name }}</h3>
        </div>
        <form action="{{ route('staff.administrator.hero-tournaments.create') }}" method="POST">
            @csrf
            <input type="hidden" name="round_id" value="{{ $round->id }}">

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Tournament Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', 'The Grand Tournament') }}" required>
                            @if ($errors->has('name'))
                                <span class="help-block text-red">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_day">Start Day of Round *</label>
                            <input type="number" name="start_day" id="start_day" class="form-control" value="{{ old('start_day') }}" min="1" required>
                            <small class="text-muted">Registration closes and matchups begin on this day</small>
                            @if ($errors->has('start_day'))
                                <span class="help-block text-red">{{ $errors->first('start_day') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Create Tournament
                </button>
                <a href="{{ route('staff.administrator.hero-tournaments.index', ['round' => $round->id]) }}" class="btn btn-default">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
