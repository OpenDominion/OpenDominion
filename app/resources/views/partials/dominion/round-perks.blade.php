<div class="card card-primary">
    <div class="card-header">
        <span class="card-title"><i class="ra ra-crystal-ball me-1"></i> {{ $selectedDominion->round->description ?: $selectedDominion->round->name }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Perk</th>
                        <th>Value</th>
                        <th>Conditions</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $roundDay = $selectedDominion->round->daysInRound();
                    @endphp
                    @foreach ($roundPerks as $row)
                        @php
                            $perk = $row['perk'];
                            $isActive = ($row['status'] === \OpenDominion\Services\RoundPerkService::STATUS_ACTIVE);
                            $cellClass = ($isActive ? null : 'text-muted');
                        @endphp
                        <tr>
                            <td>{{ $perk->name ?: '—' }}</td>
                            <td class="{{ $cellClass }}">{{ $roundPerkHelper->getPerkLabel($perk) }}</td>
                            <td class="{{ $cellClass }}">{!! $roundPerkHelper->getPerkValueHtml($perk, $isActive) !!}</td>
                            <td class="{{ $cellClass }}">{{ implode(', ', $roundPerkHelper->getPerkConditionsForDay($perk, $roundDay)) }}</td>
                            <td>{{ $perk->description ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
