<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="ra ra-telescope ra-fw"></i> Expeditions</div>
        <div class="box-tools pull-right">
            <div class="label label-primary">Exploration</div>
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
                            <th>Expedition</th>
                            <th>Morale Cost</th>
                            <th>Draftee Cost</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tactics as $tactic)
                            @php
                                $drafteeCost = $tactic->attributes['draftee_cost'];
                                $moraleCost = $tactic->attributes['morale_cost'];
                                $pointsAwarded = $tactic->attributes['points_awarded'];
                                $canPerform = $selectedDominion->military_draftees >= $drafteeCost;
                            @endphp
                            <tr>
                                <td>{{ $tactic->name }}</td>
                                <td>{{ $moraleCost }}%</td>
                                <td>{{ number_format($drafteeCost) }}</td>
                                <td>{{ number_format($pointsAwarded) }}</td>
                                <td>
                                    @if ($canPerform)
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary" {{ $selectedDominion->isLocked() || !$objective->isActive() ? 'disabled' : null }}>
                                                Explore
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-primary" disabled>
                                            Insufficient Draftees
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
                                <small class="text-muted">
                                    Draftees: {{ number_format($selectedDominion->military_draftees) }}
                                </small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
