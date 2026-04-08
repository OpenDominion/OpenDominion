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
                                <td>
                                    @if ($resourceType == 'morale')
                                        {{ number_format($amount) }}%
                                        <small class="text-red">(-{{ $amount / 10 }}% defense)</small>
                                    @else
                                        {{ number_format($amount) }}
                                    @endif
                                    <br/>
                                    <small class="text-muted">
                                        {{ ucwords(dominion_attr_display($resourceType, 100)) }}: {{ number_format($selectedDominion->{$resourceType}) }}{{ $resourceType == 'morale' ? '%' : null }}
                                    </small>
                                </td>
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
