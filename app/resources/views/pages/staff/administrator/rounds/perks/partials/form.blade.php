<div class="card-body">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="key">Perk *</label>
                <select name="key" id="key" class="form-select" required>
                    @foreach (collect($roundPerkHelper->getPerkTypes())->sortBy('label') as $key => $perkType)
                        <option value="{{ $key }}" {{ old('key', $perk?->key) === $key ? 'selected' : null }}>
                            {{ $perkType['label'] }} ({{ $key }})
                        </option>
                    @endforeach
                </select>
                @if ($errors->has('key'))
                    <span class="form-text text-red">{{ $errors->first('key') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="value">Value *</label>
                <input type="text" name="value" id="value" class="form-control" value="{{ old('value', $perk?->value) }}" required>
                <small class="text-muted">Percentage perks use whole numbers (5 = +5%). Compound values are comma delimited.</small>
                @if ($errors->has('value'))
                    <span class="form-text text-red">{{ $errors->first('value') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="alignment">Alignment</label>
                <select name="alignment" id="alignment" class="form-select">
                    <option value="">Everyone</option>
                    @foreach ($roundPerkHelper->getAlignments() as $alignment)
                        <option value="{{ $alignment }}" {{ old('alignment', $perk?->alignment) === $alignment ? 'selected' : null }}>
                            {{ ucfirst($alignment) }} races
                        </option>
                    @endforeach
                </select>
                @if ($errors->has('alignment'))
                    <span class="form-text text-red">{{ $errors->first('alignment') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="from_day">From Day</label>
                <input type="number" name="from_day" id="from_day" class="form-control" value="{{ old('from_day', $perk?->from_day) }}" min="1">
                <small class="text-muted">Inclusive. Blank = start of round.</small>
                @if ($errors->has('from_day'))
                    <span class="form-text text-red">{{ $errors->first('from_day') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="until_day">Until Day</label>
                <input type="number" name="until_day" id="until_day" class="form-control" value="{{ old('until_day', $perk?->until_day) }}" min="1">
                <small class="text-muted">Inclusive. Blank = end of round.</small>
                @if ($errors->has('until_day'))
                    <span class="form-text text-red">{{ $errors->first('until_day') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="form-control" maxlength="191" value="{{ old('name', $perk?->name) }}">
                <small class="text-muted">Perks sharing a name are grouped together.</small>
                @if ($errors->has('name'))
                    <span class="form-text text-red">{{ $errors->first('name') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-8">
            <div class="mb-3">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" class="form-control" maxlength="191" value="{{ old('description', $perk?->description) }}">
                @if ($errors->has('description'))
                    <span class="form-text text-red">{{ $errors->first('description') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
