@extends('layouts.staff')

@section('page-header', 'Create Tactic')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Create New Tactic for: {{ $objective->name }}</h3>
        </div>
        <form action="{{ route('staff.administrator.raids.objectives.tactics.create', [$raid, $objective]) }}" method="POST">
            @csrf

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Tactic Type *</label>
                            <select name="type" id="type" class="form-control" required>
                                <option value="">Select a type...</option>
                                @foreach ($tacticTypes as $type)
                                    <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
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
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
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
                            <textarea name="attributes" id="attributes" class="form-control" rows="10" required>{{ old('attributes', '{}') }}</textarea>
                            <small class="text-muted">
                                Schema will auto-populate when you select a tactic type. You can then customize the values.
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
                            <textarea name="bonuses" id="bonuses" class="form-control" rows="6">{{ old('bonuses', '{}') }}</textarea>
                            <small class="text-muted">
                                <strong>Format example:</strong> {"race": {"halfling": 1.2, "elf": 1.1}, "tech": {"spy_networks": 1.15}}
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
                    <i class="fa fa-check"></i> Create Tactic
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

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            // Schema templates for each tactic type
            var schemas = {
                @foreach ($raidHelper->getTypes() as $type)
                    '{{ $type }}': {!! json_encode($raidHelper->getTacticAttributeSchema($type), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!},
                @endforeach
            };

            // Update attributes textarea when type changes
            $('#type').on('change', function() {
                var selectedType = $(this).val();
                if (selectedType && schemas[selectedType]) {
                    var schemaJson = JSON.stringify(schemas[selectedType], null, 4);
                    $('#attributes').val(schemaJson);
                }
            });

            // Trigger on page load if type is already selected
            var initialType = $('#type').val();
            if (initialType && schemas[initialType]) {
                var currentAttributes = $('#attributes').val().trim();
                // Only populate if attributes is empty or just '{}'
                if (currentAttributes === '' || currentAttributes === '{}') {
                    var schemaJson = JSON.stringify(schemas[initialType], null, 4);
                    $('#attributes').val(schemaJson);
                }
            }
        })(jQuery);
    </script>
@endpush
