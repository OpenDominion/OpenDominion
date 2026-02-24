<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="ra ra-knight-helmet ra-fw"></i> Hero Challenges</div>
        <div class="box-tools pull-right">
            <div class="label label-primary">Hero</div>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12 table-responsive">
                <table class="table">
                    <colgroup>
                        <col width="35%">
                        <col width="30%">
                        <col width="20%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Encounter</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($selectedDominion->hero)
                            @foreach($tactics as $tactic)
                                <tr>
                                    <td>{{ $tactic->name }}</td>
                                    <td>{{ $tactic->attributes['name'] }}</td>
                                    <td>
                                        {{ number_format($raidCalculator->getTacticScore($selectedDominion, $tactic)) }}
                                        <small class="text-muted">(limit: 1)</small>
                                    </td>
                                    <td>
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary" {{ $selectedDominion->isLocked() || !$objective->isActive() ? 'disabled' : null }}>
                                                Start Battle
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @if (($tactic->attributes['encounter'] ?? null) === 'planewalker')
                                    @php
                                        $priorWins = \OpenDominion\Models\RaidContribution::where('raid_tactic_id', $tactic->id)
                                            ->where('realm_id', $selectedDominion->realm_id)
                                            ->count();
                                    @endphp
                                    <tr>
                                        <td colspan="4">
                                            <div class="alert alert-info" style="margin-bottom: 0;">
                                                <strong><i class="fa fa-shield"></i> Realm Wounds</strong><br>
                                                @if ($priorWins === 0)
                                                    No heroes in your realm have wounded the Planewalker yet. It will enter your battle at full strength.
                                                @else
                                                    <strong>{{ $priorWins }}</strong> {{ $priorWins === 1 ? 'hero has' : 'heroes have' }} already wounded the Planewalker in your realm.
                                                    It will enter your battle at <strong>{{ max(100 - ($priorWins * 10), 50) }}% strength</strong>.
                                                @endif
                                                <br/>
                                                <small class="text-muted">
                                                    <strong>Sorcerers</strong> can unleash Great Flood to strike all summoned Golems at once.<br/>
                                                    <strong>Infiltrators</strong> can pierce through the Planewalker's evasion with Shadow Strike.
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            @if ($tactics->count() > 1)
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <small class="text-muted">You can only complete one of the hero battles for this objective.</small>
                                    </td>
                                </tr>
                            @endif
                        @else
                            <tr>
                                <td colspan="4" class="text-center">
                                    <small class="text-muted">You need a hero to participate in hero challenges.</small>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
