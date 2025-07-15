<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="fa fa-user-secret fa-fw"></i> {{ $tactic->name }}</div>
        <div class="box-tools pull-right">
            <div class="label label-primary">{{ ucwords($tactic->type) }}</div>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-12 table-responsive">
                <table class="table">
                    <colgroup>
                        <col width="45%">
                        <col width="20%">
                        <col width="20%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Operation</th>
                            <th>Spy Strength</th>
                            <th>Points Awarded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tactic->attributes as $optionKey => $option)
                            @php
                                $strengthCost = $option['strength_cost'] ?? 0;
                                $pointsAwarded = $raidCalculator->getTacticPointsEarned($selectedDominion, $tactic, $option);
                                $canPerform = $selectedDominion->spy_strength >= $strengthCost;
                            @endphp
                            <tr>
                                <td>{{ $option['name'] }}</td>
                                <td>{{ $strengthCost }}%</td>
                                <td>{{ number_format($pointsAwarded) }} points</td>
                                <td>
                                    @if($canPerform)
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" name="option" value="{{ $optionKey }}" class="btn btn-block btn-sm btn-primary">
                                                Execute Operation
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-secondary" disabled>
                                            Insufficient Spy Strength
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
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
