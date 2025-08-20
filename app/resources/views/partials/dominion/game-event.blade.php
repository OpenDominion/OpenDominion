<tr>
    <td>
        @if ($selectedDominion->round->isActive())
            <span title="{{ $selectedDominion->round->getDateTooltip($gameEvent->created_at) }}" data-toggle="tooltip">
                {{ $gameEvent->created_at }}
            </span>
        @else
            <span>{{ $gameEvent->created_at }}</span>
        @endif
    </td>
    <td>
        @if ($gameEvent->type === 'invasion')
            @if ($gameEvent->source_type === \OpenDominion\Models\Dominion::class)
                @php
                    $sourceRange = round($rangeCalculator->getDominionRange($selectedDominion, $gameEvent->source), 2);
                    $sourceRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $gameEvent->source);
                    $sourceRaceName = $gameEvent->source->race->name;
                    $sourceToolTipHtml = "$sourceRaceName (<span class=\"$sourceRangeClass\">$sourceRange%</span>)";

                    $targetRange = round($rangeCalculator->getDominionRange($selectedDominion, $gameEvent->target), 2);
                    $targetRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $gameEvent->target);
                    $targetRaceName = $gameEvent->target->race->name;
                    $targetToolTipHtml = "$targetRaceName (<span class=\"$targetRangeClass\">$targetRange%</span>)";
                @endphp
                @if (in_array($gameEvent->source_id, $dominionIds, true))
                    @if ($gameEvent->data['result']['success'])
                        Victorious on the battlefield,
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-green" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                        conquered
                        <span class="text-green text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                        land from
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                    @else
                        Sadly, the forces of
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-green" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                        were beaten back by
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                    @endif
                @elseif (in_array($gameEvent->target_id, $dominionIds, true))
                    @if ($gameEvent->data['result']['success'])
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-red" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                        invaded fellow dominion
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}<span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                        and captured
                        <span class="text-red text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                        land.
                    @else
                        Fellow dominion
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                        fended off an attack from
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-red" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>.
                    @endif
                @else
                    @if ($gameEvent->data['result']['success'])
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                        invaded
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}<span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                        and captured
                        <span class="text-orange text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                        land.
                    @else
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                        fended off an attack from
                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>.
                    @endif
                @endif
            @endif
        @elseif ($gameEvent->type === 'war_declared')
            @if ($gameEvent->target_type === \OpenDominion\Models\RealmWar::class)
                <a href="{{ route('dominion.realm', [$gameEvent->target->sourceRealm->number]) }}"><span class="text-orange">{{ $gameEvent->target->source_realm_name_start }}</span> (#{{ $gameEvent->target->sourceRealm->number }})</a>
                has declared <span class="text-red text-bold">WAR</span> on
                <a href="{{ route('dominion.realm', [$gameEvent->target->targetRealm->number]) }}"><span class="text-orange">{{ $gameEvent->target->target_realm_name_start }}</span> (#{{ $gameEvent->target->targetRealm->number }})</a>.
            @else
                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a>
                has declared <span class="text-red text-bold">WAR</span> on
                <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
            @endif
        @elseif ($gameEvent->type === 'war_canceled')
            @if ($gameEvent->target_type === \OpenDominion\Models\RealmWar::class)
                <a href="{{ route('dominion.realm', [$gameEvent->target->sourceRealm->number]) }}"><span class="text-orange">{{ $gameEvent->target->source_realm_name_end }}</span> (#{{ $gameEvent->target->sourceRealm->number }})</a>
                has <span class="text-green text-bold">CANCELED</span> war against
                <a href="{{ route('dominion.realm', [$gameEvent->target->targetRealm->number]) }}"><span class="text-orange">{{ $gameEvent->target->target_realm_name_end }}</span> (#{{ $gameEvent->target->targetRealm->number }})</a>.
            @else
                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a>
                has <span class="text-green text-bold">CANCELED</span> war against
                <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
            @endif
        @elseif ($gameEvent->type == 'wonder_spawned')
            A new Wonder of the World has been discovered, the <a href="{{ route('dominion.wonders') }}"><span class="text-orange">{{ $gameEvent->source->name }}</span></a>!
        @elseif ($gameEvent->type == 'wonder_attacked')
            @php
                $sourceRange = round($rangeCalculator->getDominionRange($selectedDominion, $gameEvent->source), 2);
                $sourceRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $gameEvent->source);
                $sourceRaceName = $gameEvent->source->race->name;
                $sourceToolTipHtml = "$sourceRaceName (<span class=\"$sourceRangeClass\">$sourceRange%</span>)";
            @endphp
            @if (in_array($gameEvent->source_id, $dominionIds, true))
                <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-green" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
            @else
                <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
            @endif
            has attacked
            @if (array_get($gameEvent->data, 'wonder.neutral', true))
                a neutral wonder!
            @else
                the <a href="{{ route('dominion.wonders') }}"><span class="text-orange">{{ $gameEvent->target->wonder->name }}</span></a>
                @if ($gameEvent->target->realm !== null)
                    <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                @endif
                !
            @endif
        @elseif ($gameEvent->type == 'wonder_destroyed')
            The <a href="{{ route('dominion.wonders') }}"><span class="text-orange">{{ $gameEvent->source->wonder->name }}</span></a>
            @if ($gameEvent->target !== null)
                has been destroyed and rebuilt by
                <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
            @else
                has been destroyed!
            @endif
        @elseif ($gameEvent->type === 'wonder_invasion')
            @php
                $targetRange = round($rangeCalculator->getDominionRange($selectedDominion, $gameEvent->target), 2);
                $targetRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $gameEvent->target);
                $targetRaceName = $gameEvent->target->race->name;
                $targetToolTipHtml = "$targetRaceName (<span class=\"$targetRangeClass\">$targetRange%</span>)";
            @endphp
            <a href="{{ route('dominion.wonders') }}"><span class="text-orange">{{ $gameEvent->source->wonder->name }}</span></a>
            conquered
            <span class="{{ in_array($gameEvent->target_id, $dominionIds, true) ? 'text-red' : 'text-orange' }} text-bold">{{ number_format($gameEvent->data['landLost']) }}</span>
            land from
            <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="{{ in_array($gameEvent->target_id, $dominionIds, true) ? 'text-green' : 'text-light-blue' }}" data-toggle="tooltip" data-placement="top" title="{{ $targetToolTipHtml }}">{{ $gameEvent->target->name }}</span></a>
            <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
        @elseif ($gameEvent->type == 'raid_attacked')
            @php
                $sourceRange = round($rangeCalculator->getDominionRange($selectedDominion, $gameEvent->source), 2);
                $sourceRangeClass = $rangeCalculator->getDominionRangeSpanClass($selectedDominion, $gameEvent->source);
                $sourceRaceName = $gameEvent->source->race->name;
                $sourceToolTipHtml = "$sourceRaceName (<span class=\"$sourceRangeClass\">$sourceRange%</span>)";
            @endphp
            @if (in_array($gameEvent->source_id, $dominionIds, true))
                <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-green" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
            @else
                <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue" data-toggle="tooltip" data-placement="top" title="{{ $sourceToolTipHtml }}">{{ $gameEvent->source->name }}</span></a>
                <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
            @endif
            has attacked <a href="{{ route('dominion.raids.objective', [$gameEvent->target->raid_objective_id]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span></a>.
        @elseif ($gameEvent->type == 'abandoned')
            <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue">{{ $gameEvent->source->name }}</span></a>
            <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
            has been abandoned by its ruler.
        @endif
    </td>
    <td class="text-center">
        @if ($gameEvent->type === 'invasion')
            @if ($gameEvent->source->realm_id == $selectedDominion->realm->id || $gameEvent->target->realm_id == $selectedDominion->realm->id)
                <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
            @endif
        @elseif ($gameEvent->type === 'wonder_attacked' || $gameEvent->type === 'raid_attacked')
            @if ($gameEvent->source->realm_id == $selectedDominion->realm->id || $gameEvent->target->realm_id == $selectedDominion->realm->id)
                <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-sword ra-fw"></i></a>
            @endif
        @endif
    </td>
</tr>
