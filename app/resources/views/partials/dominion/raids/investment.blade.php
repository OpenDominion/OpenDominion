<div class="card card-outline card-primary">
    <div class="card-header">
        <div class="card-title"><i class="fa fa-money fa-fw"></i> Resource Investments</div>
        <div class="card-tools float-end">
            <div class="badge text-bg-primary">Investment</div>
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
                                $pointsAwarded = $raidCalculator->getTacticScore($selectedDominion, $tactic);
                                $canPerform = $selectedDominion->{$resourceType} >= $amount;
                                $bonusDescriptions = $raidHelper->getTacticBonusDescription($tactic->bonuses ?? []);
                            @endphp
                            <tr>
                                <td>
                                    {{ $tactic->name }}
                                    @if ($bonusDescriptions)
                                        <br/><small class="text-muted">{{ $bonusDescriptions }}</small>
                                    @endif
                                </td>
                                <td>{{ ucwords(dominion_attr_display($resourceType, $amount)) }}</td>
                                <td>{{ number_format($amount) }}</td>
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
                                                Invest
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-block btn-sm btn-primary" disabled>
                                            Insufficient {{ ucwords(dominion_attr_display($resourceType, $amount)) }}
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
                                    {{ ucwords(dominion_attr_display($resourceType, 100)) }}: {{ number_format($selectedDominion->{$resourceType}) }}
                                </small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
