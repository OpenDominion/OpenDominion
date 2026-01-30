@extends('layouts.staff')

@section('page-header', 'Edit Tactic')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Edit Tactic: {{ $tactic->name }}</h3>
        </div>
        <form action="{{ route('staff.administrator.raids.objectives.tactics.edit', [$raid, $objective, $tactic]) }}" method="POST">
            @csrf

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Tactic Type *</label>
                            <select name="type" id="type" class="form-control" required>
                                <option value="">Select a type...</option>
                                @foreach ($tacticTypes as $type)
                                    <option value="{{ $type }}" {{ (old('type', $tactic->type) == $type) ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($errors->has('type'))
                                <span class="help-block text-red">{{ $errors->first('type') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">Tactic Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $tactic->name) }}" required>
                            @if ($errors->has('name'))
                                <span class="help-block text-red">{{ $errors->first('name') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="attributes">Attributes (JSON) *</label>
                            <textarea name="attributes" id="attributes" class="form-control" rows="10" required>{{ old('attributes', json_encode($tactic->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            <small class="text-muted">
                                Modify the JSON attributes as needed for this tactic.
                            </small>
                            @if ($errors->has('attributes'))
                                <span class="help-block text-red">{{ $errors->first('attributes') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="bonuses">Bonuses (JSON)</label>
                            <textarea name="bonuses" id="bonuses" class="form-control" rows="6">{{ old('bonuses', json_encode($tactic->bonuses ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                            <small class="text-muted">
                                <strong>Format:</strong> {"hero_class": {"infiltrator": 1.25}, "race": {"halfling": 1.2}, "tech": {"tech_13_1": 1.15}}
                                <br>
                                Multipliers: 1.2 = 20% bonus, 0.9 = 10% penalty. Multiple bonuses do not stack.
                            </small>
                            @if ($errors->has('bonuses'))
                                <span class="help-block text-red">{{ $errors->first('bonuses') }}</span>
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

@push('page-styles')
    <style>
        textarea#attributes,
        textarea#bonuses {
            font-family: monospace;
        }
    </style>
@endpush
