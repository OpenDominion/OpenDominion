@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <span>{{ $gameEvent->created_at }}</span>
    {{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})

    <table class="table table-striped">
        <thead>
            <tr>
                <th>
                    Performed at
                </th>
                <th>
                    Type
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $markedOlderOps = false;
            @endphp
            @foreach ($infoOps as $infoOp)
                @if(!$markedOlderOps && $infoOp->created_at < $lastDay)
                @php
                    $markedOlderOps = true;
                @endphp
                    <tr>
                        <td colspan="2" class="text-center">
                            Ops taken 24 hours or more before event
                        </td>
                    </tr>
                @endif
                <tr>
                    <td>
                        <span>{{ $infoOp->created_at }}</span>
                    </td>
                    <td>
                        {{ $infoOp->type }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection
