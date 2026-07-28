@extends('layouts.staff')

@section('page-header', 'Round Details')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">{{ $round->name }} (Round #{{ $round->number }})</span>
            <div class="float-end">
                <a href="{{ route('staff.administrator.rounds.edit', $round) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('staff.administrator.rounds.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">League</dt>
                <dd class="col-sm-9">{{ $round->league->description ?? $round->league->key }} ({{ $round->league->key }})</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $round->description ?: '—' }}</dd>

                <dt class="col-sm-3">Start Date</dt>
                <dd class="col-sm-9">{{ $round->start_date }} ({{ $round->hasStarted() ? 'started ' . now()->longAbsoluteDiffForHumans($round->start_date, 2) . ' ago' : 'in ' . $round->timeUntilStart() }})</dd>

                <dt class="col-sm-3">End Date</dt>
                <dd class="col-sm-9">{{ $round->end_date }} ({{ $round->durationInDays() }} days)</dd>

                <dt class="col-sm-3">Registration Opens</dt>
                <dd class="col-sm-9">{{ $round->registrationOpensAt() }} — {{ $round->registrationOpen() ? 'open now' : 'in ' . now()->longAbsoluteDiffForHumans($round->registrationOpensAt(), 2) }}</dd>

                <dt class="col-sm-3">Realm Assignment</dt>
                <dd class="col-sm-9">{{ $round->realmAssignmentDate() }} — {{ $round->assignment_complete ? 'complete' : 'pending' }}</dd>

                <dt class="col-sm-3">Pack Size</dt>
                <dd class="col-sm-9">{{ $round->pack_size }}</dd>

                <dt class="col-sm-3">Players Per Race</dt>
                <dd class="col-sm-9">{{ $round->players_per_race == 0 ? 'Unlimited' : $round->players_per_race }}</dd>

                <dt class="col-sm-3">Mixed Alignment</dt>
                <dd class="col-sm-9">{{ $round->mixed_alignment ? 'Yes' : 'No' }}</dd>

                <dt class="col-sm-3">Tech Version</dt>
                <dd class="col-sm-9">{{ $round->tech_version }}</dd>

                <dt class="col-sm-3">Discord Guild ID</dt>
                <dd class="col-sm-9">{{ $round->discord_guild_id ?: '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
