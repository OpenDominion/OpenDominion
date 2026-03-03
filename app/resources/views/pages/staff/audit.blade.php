@extends('layouts.staff')

@section('page-header', 'Audit Log')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Audit Log</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <colgroup>
                    <col>
                    <col>
                    <col>
                    <col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action</th>
                        <th>Context</th>
                        <th>Performed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($activities as $activity)
                        <tr>
                            <td>{{ $activity->user->display_name }}</td>
                            <td>{{ $activity->key }}</td>
                            @if ($activity->key == 'staff.audit.invasion')
                                @php
                                    $gameEventId = isset($activity->context['gameEvent']) ? $activity->context['gameEvent'] : null;
                                    $gameEvent = \OpenDominion\Models\GameEvent::find($gameEventId);
                                @endphp
                                <td>
                                    @if ($gameEvent)
                                        {{ $gameEvent->source->name }} invaded {{ $gameEvent->target->name }} at {{ $gameEvent->created_at }}
                                    @endif
                                </td>
                            @else
                                @php
                                    $dominionId = isset($activity->context['dominion']) ? $activity->context['dominion'] : null;
                                    $dominion = \OpenDominion\Models\Dominion::find($dominionId);
                                @endphp
                                <td>
                                    @if ($dominion)
                                        {{ $dominion->name }}
                                    @endif
                                </td>
                            @endif
                            <td>{{ $activity->created_at }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <div class="float-end">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
@endsection
