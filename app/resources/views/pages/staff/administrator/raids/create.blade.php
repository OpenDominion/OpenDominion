@extends('layouts.staff')

@section('page-header', 'Create Raid')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New Raid for {{ $round->name }}</h3>
        </div>
        <form action="{{ route('staff.administrator.raids.create') }}" method="POST">
            @csrf
            <input type="hidden" name="round_id" value="{{ $round->id }}">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="name">Raid Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                            @if ($errors->has('name'))
                                <span class="form-text text-red">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea name="description" id="description" class="form-control" rows="4" required>{{ old('description') }}</textarea>
                            @if ($errors->has('description'))
                                <span class="form-text text-red">{{ $errors->first('description') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reward_resource">Reward Resource *</label>
                            <input type="text" name="reward_resource" id="reward_resource" class="form-control" value="{{ old('reward_resource', 'platinum') }}" required>
                            <small class="text-muted">e.g., platinum, lumber, ore, gems, food, mana</small>
                            @if ($errors->has('reward_resource'))
                                <span class="form-text text-red">{{ $errors->first('reward_resource') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="reward_amount">Reward Amount *</label>
                            <input type="number" name="reward_amount" id="reward_amount" class="form-control" value="{{ old('reward_amount', 0) }}" min="0" required>
                            @if ($errors->has('reward_amount'))
                                <span class="form-text text-red">{{ $errors->first('reward_amount') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="completion_reward_resource">Completion Reward Resource</label>
                            <input type="text" name="completion_reward_resource" id="completion_reward_resource" class="form-control" value="{{ old('completion_reward_resource') }}">
                            <small class="text-muted">Optional bonus for completing all objectives</small>
                            @if ($errors->has('completion_reward_resource'))
                                <span class="form-text text-red">{{ $errors->first('completion_reward_resource') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="completion_reward_amount">Completion Reward Amount</label>
                            <input type="number" name="completion_reward_amount" id="completion_reward_amount" class="form-control" value="{{ old('completion_reward_amount', 0) }}" min="0">
                            @if ($errors->has('completion_reward_amount'))
                                <span class="form-text text-red">{{ $errors->first('completion_reward_amount') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_day">Start Day of Round *</label>
                            <input type="number" name="start_day" id="start_day" class="form-control" value="{{ old('start_day', 1) }}" min="1" required>
                            @if ($errors->has('start_day'))
                                <span class="form-text text-red">{{ $errors->first('start_day') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_day">End Day of Round *</label>
                            <input type="number" name="end_day" id="end_day" class="form-control" value="{{ old('end_day') }}" min="1" required>
                            @if ($errors->has('end_day'))
                                <span class="form-text text-red">{{ $errors->first('end_day') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Create Raid
                </button>
                <a href="{{ route('staff.administrator.raids.index', ['round' => $round->id]) }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
