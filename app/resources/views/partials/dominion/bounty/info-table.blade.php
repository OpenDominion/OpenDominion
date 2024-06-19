<table class="table">
    <thead>
        <tr>
            <th class="text-center">
                <i class="fa fa-eye" title="Observation" data-toggle="tooltip"></i>
            </th>
            <th class="text-center" style="padding: 9px 0 7px 0;">
                <i class="ra ra-heavy-shield" title="Guard Status" data-toggle="tooltip"></i>
            </th>
            <th>Dominion</th>
            <th class="text-center">Race</th>
            <th class="text-center">Land</th>
            <th class="text-center">Range</th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Clear Sight (Magic)">CS</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Revelation (Magic)">Rev</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Castle Spy (Espionage)">Cas</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Barracks Spy (Espionage)">BS</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Survey Dominion (Espionage)">Sur</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Land Spy (Espionage)">Lan</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Vision (Magic)">Vis</span>
            </th>
            <th class="text-center">
                <span data-toggle="tooltip" title="Disclosure (Magic)">Dis</span>
            </th>
        </tr>
    </thead>
    <tbody>
        @if ($bounties->isEmpty())
            <tr>
                <td colspan="14" class="text-center">{{ $emptyMessage }}</td>
            </tr>
        @else
            @foreach ($bounties as $targetDominion)
                <tr>
                    <td class="text-center">
                        @if ($selectedDominion->isMonarch() || $selectedDominion->isSpymaster())
                            @if (in_array($targetDominion->id, $selectedDominion->realm->getSetting('observeDominionIds') ?? []))
                                <a href="{{ route('dominion.bounty-board.observe', $targetDominion->id) }}" data-toggle="tooltip" title="Cancel Observation">
                                    <i class="fa fa-eye-slash text-red"></i>
                                </a>
                            @else
                                <a href="{{ route('dominion.bounty-board.observe', $targetDominion->id) }}" data-toggle="tooltip" title="Mark for Observation">
                                    <i class="fa fa-eye text-green"></i>
                                </a>
                            @endif
                        @elseif (in_array($targetDominion->id, $selectedDominion->realm->getSetting('observeDominionIds') ?? []))
                            <span data-toggle="tooltip" title="Marked for Observation">
                                <i class="fa fa-eye text-aqua"></i>
                            </span>
                        @endif
                    </td>
                    <td class="text-center" style="padding: 9px 0 7px 0;">
                        @if ($guardMembershipService->isEliteGuardMember($targetDominion))
                            <i class="ra ra-heavy-shield text-yellow" title="Elite Guard" data-toggle="tooltip"></i>
                        @elseif ($guardMembershipService->isRoyalGuardMember($targetDominion))
                            <i class="ra ra-heavy-shield text-green" title="Royal Guard" data-toggle="tooltip"></i>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('dominion.op-center.show', $targetDominion->id) }}">
                            {{ $targetDominion->name }} (#{{ $targetDominion->realm->number }})
                        </a>
                    </td>
                    <td class="text-center">
                        {{ $targetDominion->race->name }}
                    </td>
                    <td class="text-center">
                        {{ $landCalculator->getTotalLand($targetDominion) }}
                    </td>
                    <td class="text-center">
                        <span class="small {{ $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $targetDominion) }}">
                            {{ number_format($rangeCalculator->getDominionRange($selectedDominion, $targetDominion), 2) }}%
                        </span>
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'magic',
                            'opType' => 'clear_sight',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'magic',
                            'opType' => 'revelation',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'espionage',
                            'opType' => 'castle_spy',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'espionage',
                            'opType' => 'barracks_spy',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'espionage',
                            'opType' => 'survey_dominion',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'espionage',
                            'opType' => 'land_spy',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'magic',
                            'opType' => 'vision',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                    <td class="text-center">
                        @include('partials.dominion.bounty.board-item', [
                            'type' => 'magic',
                            'opType' => 'disclosure',
                            'selectedDominion' => $selectedDominion,
                            'targetDominion' => $targetDominion
                        ])
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
