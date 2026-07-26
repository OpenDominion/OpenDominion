@php
    $defaults = $defaults ?? [
        'duration_in_days' => \OpenDominion\Factories\RoundFactory::ROUND_DURATION_IN_DAYS,
        'pack_size' => 5,
        'players_per_race' => 2,
        'mixed_alignment' => 1,
        'tech_version' => \OpenDominion\Helpers\TechHelper::CURRENT_VERSION,
    ];
    $currentDuration = $round ? $round->durationInDays() : $defaults['duration_in_days'];
@endphp

<div class="card-body">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="round_league_id">League *</label>
                <select name="round_league_id" id="round_league_id" class="form-select" required>
                    @foreach ($leagues as $league)
                        <option value="{{ $league->id }}" {{ old('round_league_id', $round?->round_league_id) == $league->id ? 'selected' : null }}>
                            {{ $league->description ?? $league->key }} ({{ $league->key }})
                        </option>
                    @endforeach
                </select>
                @if ($errors->has('round_league_id'))
                    <span class="form-text text-red">{{ $errors->first('round_league_id') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="name">Name *</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $round?->name) }}" required>
                @if ($errors->has('name'))
                    <span class="form-text text-red">{{ $errors->first('name') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <label for="description">Description</label>
                <input type="text" name="description" id="description" class="form-control" maxlength="128" value="{{ old('description', $round?->description) }}">
                <small class="text-muted">Optional short blurb shown in the calendar and next-round banner (max 128 chars).</small>
                @if ($errors->has('description'))
                    <span class="form-text text-red">{{ $errors->first('description') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="start_date">Start Date *</label>
                <input type="datetime-local" name="start_date" id="start_date" class="form-control"
                       value="{{ old('start_date', $round?->start_date?->format('Y-m-d\TH:i')) }}" required>
                @if ($errors->has('start_date'))
                    <span class="form-text text-red">{{ $errors->first('start_date') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="duration_in_days">Duration (days) *</label>
                <input type="number" name="duration_in_days" id="duration_in_days" class="form-control"
                       value="{{ old('duration_in_days', $currentDuration) }}" min="1" required>
                <small class="text-muted">End date will be Start Date + this many days.</small>
                @if ($errors->has('duration_in_days'))
                    <span class="form-text text-red">{{ $errors->first('duration_in_days') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-3">
                <label for="pack_size">Pack Size *</label>
                <input type="number" name="pack_size" id="pack_size" class="form-control"
                       value="{{ old('pack_size', $round?->pack_size ?? $defaults['pack_size']) }}" min="1" required>
                @if ($errors->has('pack_size'))
                    <span class="form-text text-red">{{ $errors->first('pack_size') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="players_per_race">Players Per Race *</label>
                <input type="number" name="players_per_race" id="players_per_race" class="form-control"
                       value="{{ old('players_per_race', $round?->players_per_race ?? $defaults['players_per_race']) }}" min="0" required>
                <small class="text-muted">0 = unlimited.</small>
                @if ($errors->has('players_per_race'))
                    <span class="form-text text-red">{{ $errors->first('players_per_race') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label for="tech_version">Tech Version *</label>
                <input type="number" name="tech_version" id="tech_version" class="form-control"
                       value="{{ old('tech_version', $round?->tech_version ?? $defaults['tech_version']) }}" min="1" required>
                @if ($errors->has('tech_version'))
                    <span class="form-text text-red">{{ $errors->first('tech_version') }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="mixed_alignment">Mixed Alignment *</label>
                <select name="mixed_alignment" id="mixed_alignment" class="form-select" required>
                    <option value="1" {{ old('mixed_alignment', $round?->mixed_alignment ?? $defaults['mixed_alignment']) ? 'selected' : null }}>Yes</option>
                    <option value="0" {{ !old('mixed_alignment', $round?->mixed_alignment ?? $defaults['mixed_alignment']) ? 'selected' : null }}>No</option>
                </select>
                @if ($errors->has('mixed_alignment'))
                    <span class="form-text text-red">{{ $errors->first('mixed_alignment') }}</span>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="discord_guild_id">Discord Guild ID</label>
                <input type="text" name="discord_guild_id" id="discord_guild_id" class="form-control"
                       value="{{ old('discord_guild_id', $round?->discord_guild_id) }}">
                @if ($errors->has('discord_guild_id'))
                    <span class="form-text text-red">{{ $errors->first('discord_guild_id') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
