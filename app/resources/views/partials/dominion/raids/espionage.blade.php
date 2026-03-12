<div class="card card-outline card-primary">
    <div class="card-header">
        <div class="card-title"><i class="fa fa-user-secret fa-fw"></i> Espionage Operations</div>
        <div class="card-tools float-end">
            <div class="badge text-bg-primary">Espionage</div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 table-responsive">
                <table class="table">
                    <colgroup>
                        <col width="25%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Operation</th>
                            <th>Morale</th>
                            <th>Spy Strength</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tactics as $tactic)
                            @php
                                $moraleCost = $tactic->attributes['morale_cost'];
                                $strengthCost = $tactic->attributes['strength_cost'];
                                $pointsAwarded = $raidCalculator->getTacticScore($selectedDominion, $tactic);
                                $canPerform = $selectedDominion->spy_strength >= $strengthCost;
                                $bonusDescriptions = $raidHelper->getTacticBonusDescription($tactic->bonuses ?? []);
                            @endphp
                            <tr>
                                <td>
                                    {{ $tactic->name }}
                                    @if ($bonusDescriptions)
                                        <br/><small class="text-muted">{{ $bonusDescriptions }}</small>
                                    @endif
                                </td>
                                <td>{{ $moraleCost }}%</td>
                                <td>{{ $strengthCost }}%</td>
                                <td>
                                    {{ number_format($pointsAwarded) }}
                                    @if(isset($tactic->attributes['limit']))
                                        <small class="text-muted">(limit: {{ $tactic->attributes['limit'] }})</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($canPerform)
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary" {{ $selectedDominion->isLocked() || !$objective->isActive() ? 'disabled' : null }}>
                                                Execute Operation
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-primary" disabled>
                                            Insufficient Spy Strength
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td>
                                <small class="text-muted">
                                    Morale: {{ $selectedDominion->morale }}%
                                </small>
                            </td>
                            <td colspan=3>
                                <small class="text-muted">Spy Strength: {{ sprintf("%.4g", $selectedDominion->spy_strength) }}%</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
