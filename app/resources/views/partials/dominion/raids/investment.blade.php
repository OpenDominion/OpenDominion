<div class="box box-primary">
    <div class="box-header with-border">
        <div class="box-title"><i class="fa fa-money fa-fw"></i> {{ $tactic->name }}</div>
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
                            <th>Resource</th>
                            <th>Amount</th>
                            <th>Points Awarded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tactic->attributes as $optionKey => $option)
                            @php
                                $resourceType = $option['resource'];
                                $resourceCost = $option['amount'];
                                $canPerform = $selectedDominion->{"resource_{$resourceType}"} >= $resourceCost;
                            @endphp
                            <tr>
                                <td>{{ $option['name'] }}</td>
                                <td>{{ number_format($option['amount']) }} {{ $option['resource'] }}</td>
                                <td>{{ $option['points_awarded'] }}</td>
                                <td>
                                    @if ($canPerform)
                                        <form action="{{ route('dominion.raids.tactic', $tactic) }}" method="post">
                                            @csrf
                                            <button type="submit" name="option" value="{{ $optionKey }}" class="btn btn-block btn-sm btn-primary">
                                                Invest
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-secondary" disabled>
                                            Insufficient Resources
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
