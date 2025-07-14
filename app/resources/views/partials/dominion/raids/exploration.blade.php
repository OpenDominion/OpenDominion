<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="ra ra-telescope ra-fw"></i> {{ $tactic->name }}</div>
        <div class="box-tools pull-right">
            <div class="label label-primary">{{ ucwords($tactic->type) }}</div>
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
                            <th>Location</th>
                            <th>Morale</th>
                            <th>Draftees</th>
                            <th>Points Awarded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tactic->attributes as $optionKey => $option)
                            <tr>
                                <td>{{ $option['name'] }}</td>
                                <td>{{ number_format($option['morale_cost']) }}%</td>
                                <td>{{ number_format($option['draftee_cost']) }}</td>
                                <td>{{ $option['points_awarded'] }} points</td>
                                <td>
                                    @if ($selectedDominion->military_draftees >= $option['draftee_cost'])
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" name="option" value="{{ $optionKey }}" class="btn btn-block btn-sm btn-primary">
                                                Explore
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-secondary" disabled>
                                            Insufficient Draftees
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td>
                                <small class="text-muted">Morale: {{ number_format($selectedDominion->morale) }}%</small>
                            </td>
                            <td colspan=3>
                                <small class="text-muted">Draftees: {{ number_format($selectedDominion->military_draftees) }}</small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
