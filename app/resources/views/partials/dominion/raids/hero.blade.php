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
                        <col width="45%">
                        <col width="20%">
                        <col width="20%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Challenge</th>
                            <th>Opponent</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($selectedDominion->hero)
                            @foreach($tactics as $tactic)
                                <tr>
                                    <td>{{ $tactic->name }}</td>
                                    <td>{{ $tactic->attributes['name'] }}</td>
                                    <td>{{ number_format($raidCalculator->getTacticScore($selectedDominion, $tactic)) }}</td>
                                    <td>
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary" {{ $selectedDominion->isLocked() || !$objective->isActive() ? 'disabled' : null }}>
                                                Start Battle
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
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
