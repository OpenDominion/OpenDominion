@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <span>{{ $gameEvent->created_at }}</span>
    {{ $gameEvent->target->name }} (#{{ $gameEvent->target->realm->number }})

    <table class="table table-striped">
        <thead>
            <tr>
            </tr>
        </thead>
        <tbody>
            @foreach ($infoOps as $infoOp)
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
