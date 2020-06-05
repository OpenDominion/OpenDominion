@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    {{ $dominion->name }}
    {{ $dominion->created_at }}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>
                    Date
                </th>
                <th>
                    Source
                </th>
                <th>
                    Target
                </th>
                <th>
                    Result
                </th>
                <th>
                    &nbsp;
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($gameEvents as $gameEvent)
                @if ($gameEvent->type !== 'invasion')
                    @continue
                @endif
                <tr>
                    <td>
                        <span>{{ $gameEvent->created_at }}</span>
                    </td>
                    <td>
                        {{ $gameEvent->source->name }} (#{{ $gameEvent->source->realm->number }})
                    </td>
                    <td>
                        {{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})
                    </td>
                    <td>
                        @if ($gameEvent->data['result']['success'])
                            Success
                        @else
                            Failure
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('staff.moderator.dominion.event', [$dominion->id, $gameEvent->id]) }}">Investigate</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection
