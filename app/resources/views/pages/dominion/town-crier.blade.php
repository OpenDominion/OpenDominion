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
                                @foreach ($gameEvents as $gameEvent)
                                    <tr>
                                        <td>
                                            <span>{{ $gameEvent->created_at }}</span>
                                        </td>
                                        <td>
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source_type === \OpenDominion\Models\Dominion::class && in_array($gameEvent->source_id, $dominionIds, true))
                                                    @if ($gameEvent->data['result']['success'])
                                                        Victorious on the battlefield,
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-green">{{ $gameEvent->source->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                                                        conquered
                                                        <span class="text-green text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                        land from
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-red">{{ $gameEvent->target->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                                                    @else
                                                        Sadly, the forces of
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-green">{{ $gameEvent->source->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                                                        were beaten back by
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-red">{{ $gameEvent->target->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>.
                                                    @endif
                                                @elseif ($gameEvent->target_type === \OpenDominion\Models\Dominion::class)
                                                    @if ($gameEvent->data['result']['success'])
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue">{{ $gameEvent->source->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>
                                                        invaded
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue">{{ $gameEvent->target->name }}<span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                                                        and captured
                                                        <span class="text-orange text-bold">{{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}</span>
                                                        land.
                                                    @else
                                                        @if ($gameEvent->source_realm_id == $selectedDominion->realm_id)
                                                            Fellow dominion
                                                        @endif
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->target->id]) }}"><span class="text-light-blue">{{ $gameEvent->target->name }}</span></a> <a href="{{ route('dominion.realm', [$gameEvent->target->realm->number]) }}">(#{{ $gameEvent->target->realm->number }})</a>
                                                        fended off an attack from
                                                        <a href="{{ route('dominion.op-center.show', [$gameEvent->source->id]) }}"><span class="text-light-blue">{{ $gameEvent->source->name }}</span></a>
                                                        <a href="{{ route('dominion.realm', [$gameEvent->source->realm->number]) }}">(#{{ $gameEvent->source->realm->number }})</a>.
                                                    @endif
                                                @endif
                                            @elseif ($gameEvent->type === 'war_declared')
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a> has declared <span class="text-red text-bold">WAR</span> on <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
                                            @elseif ($gameEvent->type === 'war_canceled')
                                                <a href="{{ route('dominion.realm', [$gameEvent->source->number]) }}"><span class="text-orange">{{ $gameEvent->source->name }}</span> (#{{ $gameEvent->source->number }})</a> has <span class="text-green text-bold">CANCELED</span> war against realm <a href="{{ route('dominion.realm', [$gameEvent->target->number]) }}"><span class="text-orange">{{ $gameEvent->target->name }}</span> (#{{ $gameEvent->target->number }})</a>.
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source->realm_id == $selectedDominion->realm->id || $gameEvent->target->realm_id == $selectedDominion->realm->id)
                                                    <a href="{{ route('dominion.event', [$gameEvent->id]) }}"><i class="ra ra-crossed-swords ra-fw"></i></a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
                        <p>All the news for the target's realm will be shown here. The town crier presents news for a 2 day period.</p>
                    @else
                        <p>All the news for your realm can be seen here. The town crier gives you news for a 2 day period.</p>
                    @endif
                    <p>You will see only military operations, as well as death messages{{-- and important messages regarding Wonders of the World--}}. Magical and Spy attacks are not known to the Town Crier, and you will have to inquire in the council as to those types of attacks.</p>
                    @if ($selectedDominion->round->start_date <= now())
                    <p>
                        <label for="realm-select">Show Town Crier for:</label>
                        <select id="realm-select" class="form-control">
                            <option value="">All Realms</option>
                            @for ($i=1; $i<=$realmCount; $i++)
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
