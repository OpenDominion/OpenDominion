<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="ra ra-knight-helmet ra-fw"></i> {{ $tactic->name }}</div>
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
                            <th>Name</th>
                            <th>Opponent</th>
                            <th>Points Awarded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($selectedDominion->hero)
                            <tr>
                                <td>{{ $tactic->name }}</td>
                                <td>{{ $tactic->attributes['name'] }}</td>
                                <td>{{ $tactic->attributes['points_awarded'] }} points</td>
                                <td>
                                    <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                        @csrf
                                        <button type="submit" class="btn btn-block btn-sm btn-primary">
                                            Deploy Hero
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    You need a hero to perform hero operations.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
