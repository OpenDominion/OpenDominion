<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="fa fa-money fa-fw"></i> Resource Investments</div>
        <div class="box-tools pull-right">
            <div class="label label-primary">Investment</div>
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
                            <th>Investment</th>
                            <th>Resource</th>
                            <th>Amount</th>
                            <th>Points</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tactics as $tactic)
                            @php
                                $resourceType = $tactic->attributes['resource'];
                                $amount = $tactic->attributes['amount'];
                                $pointsAwarded = $tactic->attributes['points_awarded'];
                                $canPerform = $selectedDominion->{"resource_{$resourceType}"} >= $amount;
                            @endphp
                            <tr>
                                <td>{{ $tactic->name }}</td>
                                <td>{{ ucfirst($resourceType) }}</td>
                                <td>{{ number_format($amount) }}</td>
                                <td>{{ number_format($pointsAwarded) }}</td>
                                <td>
                                    @if ($canPerform)
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" class="btn btn-block btn-sm btn-primary">
                                                Invest
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-primary" disabled>
                                            Insufficient {{ ucfirst($resourceType) }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td></td>
                            <td colspan=3>
                                <small class="text-muted">
                                    {{ ucwords($resourceType) }}: {{ number_format($selectedDominion->{"resource_{$resourceType}"}) }}
                                </small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
