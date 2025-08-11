<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="ra ra-fairy-wand ra-fw"></i> Magic Spells</div>
        <div class="box-tools pull-right">
            <div class="label label-primary">Magic</div>
        </div>
    </div>
    <div class="box-body">
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
                            <th>Spell</th>
                            <th>Mana Cost</th>
                            <th>Wizard Strength</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tactics as $tactic)
                            @php
                                $actualManaCost = $raidCalculator->getTacticManaCost($selectedDominion, $tactic);
                                $manaCostMultiplier = $tactic->attributes['mana_cost'];
                                $wizardStrengthRequired = $tactic->attributes['strength_cost'];
                                $pointsAwarded = $raidCalculator->getTacticPointsEarned($selectedDominion, $tactic);
                                $canPerform = $selectedDominion->resource_mana >= $actualManaCost && $selectedDominion->wizard_strength >= max(30, $wizardStrengthRequired);
                            @endphp
                            <tr>
                                <td>{{ $tactic->name }}</td>
                                <td>
                                    {{ number_format($actualManaCost) }}
                                    <small class="text-muted">({{ $manaCostMultiplier }}x)</small>
                                </td>
                                <td>{{ $wizardStrengthRequired }}%</td>
                                <td>{{ number_format($pointsAwarded) }}</td>
                                <td>
                                    @if($canPerform)
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary" {{ $selectedDominion->isLocked() || $objective->raid->hasEnded() ? 'disabled' : null }}>
                                                Cast Spell
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-primary" disabled>
                                            @if($selectedDominion->resource_mana < $actualManaCost)
                                                Insufficient Mana
                                            @else
                                                Insufficient Wizard Strength
                                            @endif
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td>
                                <small class="text-muted">Mana: {{ number_format($selectedDominion->resource_mana) }}</small>
                            </td>
                            <td colspan=3>
                                <small class="text-muted">Wizard Strength: {{ sprintf("%.4g", $selectedDominion->wizard_strength) }}%</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
