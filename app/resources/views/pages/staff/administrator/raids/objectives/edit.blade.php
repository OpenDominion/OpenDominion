@extends('layouts.staff')

@section('page-header', 'Edit Objective')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Edit Objective: {{ $objective->name }}</h3>
        </div>
        <form action="{{ route('staff.administrator.raids.objectives.edit', [$raid, $objective]) }}" method="POST">
            @csrf

            <div class="box-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="name">Objective Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $objective->name) }}" required>
                            @if ($errors->has('name'))
                                <span class="help-block text-red">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="order">Display Order *</label>
                            <input type="number" name="order" id="order" class="form-control" value="{{ old('order', $objective->order) }}" min="0" required>
                            <small class="text-muted">Lower numbers appear first</small>
                            @if ($errors->has('order'))
                                <span class="help-block text-red">{{ $errors->first('order') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea name="description" id="description" class="form-control" rows="4" required>{{ old('description', preg_replace('/<br\s*\/?>/i', "\n", $objective->description)) }}</textarea>
                            @if ($errors->has('description'))
                                <span class="help-block text-red">{{ $errors->first('description') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="score_required">Score Required to Complete *</label>
                            <input type="number" name="score_required" id="score_required" class="form-control" value="{{ old('score_required', $objective->score_required) }}" min="1" required>
                            <small class="text-muted">Total realm score needed to complete this objective</small>
                            @if ($errors->has('score_required'))
                                <span class="help-block text-red">{{ $errors->first('score_required') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_day">Start Day of Round *</label>
                            <input type="number" name="start_day" id="start_day" class="form-control" value="{{ old('start_day', $raid->round->daysInRound($objective->start_date)) }}" min="1" required>
                            @if ($errors->has('start_day'))
                                <span class="help-block text-red">{{ $errors->first('start_day') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_day">End Day of Round *</label>
                            <input type="number" name="end_day" id="end_day" class="form-control" value="{{ old('end_day', $raid->round->daysInRound($objective->end_date)) }}" min="1" required>
                            @if ($errors->has('end_day'))
                                <span class="help-block text-red">{{ $errors->first('end_day') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Save Changes
                </button>
                <a href="{{ route('staff.administrator.raids.objectives.show', [$raid, $objective]) }}" class="btn btn-default">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
