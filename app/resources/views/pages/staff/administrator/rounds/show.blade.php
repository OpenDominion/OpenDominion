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

    <div class="card">
        <div class="card-header">
            <span class="card-title">Round Perks</span>
            <div class="float-end">
                <a href="{{ route('staff.administrator.rounds.perks.create', $round) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add Perk
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if ($round->perks->isEmpty())
                <p class="p-3 mb-0 text-muted">No perks for this round.</p>
            @else
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Perk</th>
                            <th>Value</th>
                            <th>Conditions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($round->perks->sortBy(['name', 'id']) as $perk)
                            <tr>
                                <td>
                                    {{ $perk->name ?: '—' }}
                                    @if ($perk->description)
                                        <br><small class="text-muted">{{ $perk->description }}</small>
                                    @endif
                                </td>
                                <td>{{ $roundPerkHelper->getPerkLabel($perk) }} <small class="text-muted">({{ $perk->key }})</small></td>
                                <td>{!! $roundPerkHelper->getPerkValueHtml($perk) !!}</td>
                                <td>{{ implode(', ', $roundPerkHelper->getPerkConditions($perk)) ?: 'Always' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('staff.administrator.rounds.perks.edit', [$round, $perk]) }}" class="btn btn-secondary btn-sm">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <form action="{{ route('staff.administrator.rounds.perks.delete', [$round, $perk]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this perk?');">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card" id="storylines">
                <div class="card-header">
                    <span class="card-title">Town Crier Storylines</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.administrator.rounds.storylines.create', $round) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="storyline-text">Text *</label>
                            <textarea name="text" id="storyline-text" class="form-control" rows="4" required>{{ old('text') }}</textarea>
                            <small class="text-muted">Shown in the round-wide Town Crier. Line breaks are kept; HTML is allowed.</small>
                            @if ($errors->has('text'))
                                <span class="form-text text-red">{{ $errors->first('text') }}</span>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="storyline-post-at">Post at (UTC)</label>
                                    <input type="datetime-local" name="post_at" id="storyline-post-at" class="form-control" value="{{ old('post_at', now()->format('Y-m-d\TH:i')) }}">
                                    @if ($errors->has('post_at'))
                                        <span class="form-text text-red">{{ $errors->first('post_at') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small">
                            Server time is now <strong>{{ now()->format('M d, Y H:i') }} UTC</strong>.
                            Keep the current time to post now; a later UTC time keeps it hidden until then.
                        </p>
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Post Storyline</button>
                        </div>
                    </form>

                    @if ($round->storylineEvents->isNotEmpty())
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th width="180">Posted</th>
                                    <th>Text</th>
                                    <th width="80" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($round->storylineEvents->sortByDesc('created_at') as $storyline)
                                    <tr>
                                        <td class="text-nowrap">
                                            Day {{ $round->daysInRound($storyline->created_at) }}
                                            <small class="text-muted">({{ $storyline->created_at->format('M d, Y H:i') }})</small>
                                            @if ($storyline->created_at->isFuture())
                                                <br><span class="badge text-bg-warning">Scheduled</span>
                                            @endif
                                        </td>
                                        <td>{!! $storyline->data['text'] ?? '' !!}</td>
                                        <td class="text-end text-nowrap">
                                            <form action="{{ route('staff.administrator.rounds.storylines.delete', [$round, $storyline]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this storyline?');">
                                                @csrf
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
