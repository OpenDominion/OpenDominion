<table class="table">
    <thead>
        <tr>
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
                <td colspan="12" class="text-center">{{ $emptyMessage }}</td>
            </tr>
        @else
            @foreach ($bounties as $targetDominion)
                <tr>
                    <td>
                        @if ($selectedDominion->isMonarch() || $selectedDominion->isSpymaster())
                            @if (in_array($targetDominion->id, $selectedDominion->realm->getSetting('observeDominionIds') ?? []))
                                <a href="{{ route('dominion.bounty-board.observe', $targetDominion->id) }}" data-toggle="tooltip" title="Cancel Observation">
                                    <i class="fa fa-eye-slash text-red" style="margin-right: 5px;"></i>
                                </a>
                            @else
                                <a href="{{ route('dominion.bounty-board.observe', $targetDominion->id) }}" data-toggle="tooltip" title="Mark for Observation">
                                    <i class="fa fa-eye text-aqua" style="margin-right: 5px;"></i>
                                </a>
                            @endif
                        @else
                            <span data-toggle="tooltip" title="Marked for Observation">
                                <i class="fa fa-eye" style="margin-right: 5px;"></i>
                            </span>
                        @endif
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
