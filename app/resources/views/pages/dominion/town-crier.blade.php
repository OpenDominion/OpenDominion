@extends('layouts.master')

@section('page-header', 'Town Crier')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-newspaper-o"></i> Town Crier for {{ $realm->name }} (#{{ $realm->number }})
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
                                <col>
                                <col width="50">
                                <col width="200">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th class="text-center">Link</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($gameEvents as $gameEvent)
                                    <tr>
                                        <td>
                                            @if ($gameEvent->type === 'invasion')
                                                @if ($gameEvent->source_type === \OpenDominion\Models\Dominion::class && in_array($gameEvent->source_id, $dominionIds, true))
                                                    @if ($gameEvent->data['result']['success'])
                                                        Victorious on the battlefield, realmie
                                                        <span class="text-green">{{ $gameEvent->source->name }} (#{{ $gameEvent->source->realm->number }})</span>
                                                        conquered
                                                        {{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}
                                                        land from
                                                        <span class="text-red">{{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})</span>.
                                                    @else
                                                        Sadly, the forces of realmie
                                                        <span class="text-green">{{ $gameEvent->source->name }} (#{{ $gameEvent->source->realm->number }})</span>
                                                        were beaten back by
                                                        <span class="text-red">{{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})</span>.
                                                    @endif
                                                @elseif ($gameEvent->target_type === \OpenDominion\Models\Dominion::class && in_array($gameEvent->target_id, $dominionIds, true))
                                                    @if ($gameEvent->data['result']['success'])
                                                        <span class="text-red">{{ $gameEvent->source->name }} (#{{ $gameEvent->source->realm->number }})</span>
                                                        invaded our realmie
                                                        <span class="text-green">{{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})</span>
                                                        and captured
                                                        {{ number_format(array_sum($gameEvent->data['attacker']['landConquered'])) }}
                                                        land.
                                                    @endif
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($gameEvent->source->realm_id == $selectedDominion->realm->id)
                                                <a href="{{ route('dominion.event', [$gameEvent->id]) }}">Link</a>
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span title="{{ $gameEvent->created_at }}">{{ $gameEvent->created_at->diffForHumans() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($fromOpCenter)
                            <div class="box-footer">
                                <em>Revealed <abbr title="{{ $clairvoyanceInfoOp->updated_at }}">{{ $clairvoyanceInfoOp->updated_at->diffForHumans() }}</abbr> by {{ $clairvoyanceInfoOp->sourceDominion->name }}</em>
                            </div>
                        @endif
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
                    <p>All the news for your realm can be seen here. The town crier gives you news for a 2 day period.</p>
                    <p>You will see only military operations, as well as death messages{{-- and important messages regarding Wonders of the World--}}. Magical and Spy attacks are not known to the Town Crier, and you will have to inquire in the council as to those types of attacks.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
