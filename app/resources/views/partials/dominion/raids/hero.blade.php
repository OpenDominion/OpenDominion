<div class="card card-primary">
    <div class="card-header">
        <div class="card-title"><i class="ra ra-knight-helmet ra-fw"></i> Hero Challenges</div>
        <div class="card-tools float-end">
            <div class="badge text-bg-primary">Hero</div>
        </div>
    </div>
    <div class="card-body">
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
                            @php
                                $combatRegistry = app(\OpenDominion\HeroCombat\Registry\CombatRegistry::class);
                                $heroHelper = app(\OpenDominion\Helpers\HeroHelper::class);
                                $companions = null;
                            @endphp
                            @foreach($tactics as $tactic)
                                @php
                                    $encounterKey = $tactic->attributes['encounter'] ?? null;
                                    $encounter = $encounterKey && $combatRegistry->hasEncounter($encounterKey) ? $combatRegistry->encounter($encounterKey) : null;
                                    $partySize = $encounter?->maxPartySize() ?? 1;
                                    $minPartySize = $encounter?->minPartySize() ?? 1;
                                    if ($partySize > 1 && $companions === null) {
                                        $companions = app(\OpenDominion\Services\Dominion\HeroBattleService::class)->availableCompanions($selectedDominion);
                                    }
                                    $needsCompanion = $minPartySize > 1 && $companions->isEmpty();
                                    $showPartyRow = $partySize > 1 && ($companions->isNotEmpty() || $needsCompanion);
                                    $formId = "raid-tactic-{$tactic->id}";
                                @endphp
                                <tr>
                                    <td @class(['border-bottom-0' => $showPartyRow])>{{ $tactic->name }}</td>
                                    <td @class(['border-bottom-0' => $showPartyRow])>{{ $tactic->attributes['name'] }}</td>
                                    <td @class(['border-bottom-0' => $showPartyRow])>
                                        {{ number_format($raidCalculator->getTacticScore($selectedDominion, $tactic)) }}
                                        <small class="text-muted">(limit: 1)</small>
                                    </td>
                                    <td @class(['border-bottom-0' => $showPartyRow])>
                                        <form id="{{ $formId }}" action="{{ route('dominion.raids.tactic', $tactic) }}" method="post" class="disable-after-click">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary" {{ $selectedDominion->isLocked() || !$objective->isActive() || $needsCompanion ? 'disabled' : null }}>
                                                Start Battle
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @if ($showPartyRow)
                                    <tr>
                                        <td colspan="2" class="pt-0"></td>
                                        <td colspan="2" class="pt-0">
                                            @if ($companions->isNotEmpty())
                                                <select name="companion_hero_id" form="{{ $formId }}" class="form-select form-select-sm" title="Bring a realmmate's hero">
                                                    @if ($minPartySize <= 1)
                                                        <option value="">Go alone</option>
                                                    @endif
                                                    @foreach ($companions as $companion)
                                                        <option value="{{ $companion->id }}">{{ $companion->name }} ({{ $heroHelper->getClassDisplayName($companion->class) }}, {{ $companion->dominion->name }})</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($needsCompanion)
                                                <small class="text-muted">Needs a realmmate's hero</small>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
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
                                                    <strong>Engineers</strong> can use explosives to damage all enemies at once.<br/>
                                                    <strong>Sorcerers</strong> can unleash Great Flood to damage all enemies at once.<br/>
                                                    <strong>Infiltrators</strong> can pierce through the Planewalker's evasion with Shadow Strike.
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                @if (($tactic->attributes['encounter'] ?? null) === 'dreadsoul_skullkeeper')
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
                                                    No heroes in your realm have wounded Dreadsoul yet. He will enter your battle at full strength.
                                                @else
                                                    <strong>{{ $priorWins }}</strong> {{ $priorWins === 1 ? 'hero has' : 'heroes have' }} already wounded Dreadsoul in your realm.
                                                    He will enter your battle at <strong>{{ max(100 - ($priorWins * 2), 50) }}% health</strong>.
                                                @endif
                                                <br/>
                                                <small class="text-muted">
                                                    Dreadsoul's guards will throw themselves at you. Beware — when they are defeated, their strength flows into him.
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
