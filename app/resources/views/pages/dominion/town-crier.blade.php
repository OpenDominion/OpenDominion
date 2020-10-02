@extends('layouts.master')

@section('page-header', 'Town Crier')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-newspaper-o"></i> Town Crier for
                        @if ($realm !== null)
                            {{ $realm->name }} (#{{ $realm->number }})
                        @else
                            All Realms
                        @endif
                    </h3>
                </div>

                @if ($gameEvents->isEmpty())
                    <div class="box-body">
                        <p>No recent events.</p>
                    </div>
                @else
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-striped">
                            <colgroup>
                                <col width="140">
                                <col>
                                <col width="50">
                            </colgroup>
                            <tbody>
                                @php
                                    $previousDate = null;
                                    $firstLoop = true;
                                @endphp
                                @foreach ($gameEvents as $gameEvent)
                                    @if($previousDate != $gameEvent->created_at->startOfDay())
                                        <tr>
                                            <td colspan="3" class="text-center text-bold border-left border-right">
                                                News from {{ $gameEvent->created_at->toDateString() }}
                                            </td>
                                        </tr>
                                        @php
                                            $previousDate = $gameEvent->created_at->startOfDay();
                                            $firstLoop = false;
                                        @endphp
                                    @endif
                                    <tr>
                                        <td>
                                            <span>{{ $gameEvent->created_at }}</span>
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
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a>
                                                has declared <span class="text-red text-bold">WAR</span> on
                                                <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
                                            @elseif ($gameEvent->type === 'war_canceled')
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a>
                                                has <span class="text-green text-bold">CANCELED</span> war against
                                                <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
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
                                                @if (array_get($gameEvent->data, 'wonder.neutral', true) || $gameEvent->target->realm == null)
                                                    a neutral wonder!
                                                @else
                                                    the <a href="{{ route('dominion.wonders') }}"><span class="text-orange">{{ $gameEvent->target->wonder->name }}</span></a>
                                                    <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>!
                                                @endif
                                            @elseif ($gameEvent->type == 'wonder_destroyed')
                                                The <a href="{{ route('dominion.wonders') }}"><span class="text-orange">{{ $gameEvent->source->wonder->name }}</span></a>
                                                @if ($gameEvent->target !== null)
                                                    has been destroyed and rebuilt by
                                                    <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
                                                @else
                                                    has been destroyed!
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source->realm_id == $selectedDominion->realm->id || $gameEvent->target->realm_id == $selectedDominion->realm->id)
                                                    <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
                                                @endif
                                            @elseif ($gameEvent->type === 'wonder_attacked')
                                                @if ($gameEvent->source->realm_id == $selectedDominion->realm->id || $gameEvent->target->realm_id == $selectedDominion->realm->id)
                                                    <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-sword ra-fw"></i></a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            {{ $gameEvents->links() }}
                        </div>
                    </div>
                @endif
                @if ($fromOpCenter)
                    <div class="box-footer">
                        <em>Revealed {{ $clairvoyanceInfoOp->updated_at }} by {{ $clairvoyanceInfoOp->sourceDominion->name }}</em>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    @if ($fromOpCenter)
                        <p>All the news for the target's realm will be shown here.</p>
                    @else
                        <p>All the news for your realm can be seen here.</p>
                    @endif
                    <p>You will see only military operations and important messages regarding Wonders of the World. Magical and Spy operations are not known to the Town Crier.</p>
                    @if ($selectedDominion->round->start_date <= now())
                    <p>
                        <label for="realm-select">Show Town Crier for:</label>
                        <select id="realm-select" class="form-control">
                            <option value="">All Realms</option>
                            @for ($i=0; $i<$realmCount; $i++)
                                <option value="{{ $i }}" {{ $realm && $realm->number == $i ? 'selected' : null }}>
                                    #{{ $i }} {{ $selectedDominion->realm->number == $i ? '(My Realm)' : null }}
                                </option>
                            @endfor
                        </select>
                    </p>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#realm-select').change(function() {
                var selectedRealm = $(this).val();
                window.location.href = "{!! route('dominion.town-crier') !!}/" + selectedRealm;
            });
        })(jQuery);
    </script>
@endpush
