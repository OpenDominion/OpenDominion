@extends('layouts.staff')

@section('page-header', 'Edit Hero Tournament')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Edit Hero Tournament: {{ $tournament->name }}</h3>
        </div>
        <form action="{{ route('staff.administrator.hero-tournaments.edit', $tournament) }}" method="POST">
            @csrf

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Tournament Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $tournament->name) }}" required>
                            @if ($errors->has('name'))
                                <span class="help-block text-red">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_day">Start Day of Round *</label>
                            @php
                                $currentStartDay = $tournament->start_date
                                    ? $tournament->round->daysInRound($tournament->start_date)
                                    : null;
                            @endphp
                            <input type="number" name="start_day" id="start_day" class="form-control" value="{{ old('start_day', $currentStartDay) }}" min="1" required>
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
                    <i class="fa fa-check"></i> Update Tournament
                </button>
                <a href="{{ route('staff.administrator.hero-tournaments.show', $tournament) }}" class="btn btn-default">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
