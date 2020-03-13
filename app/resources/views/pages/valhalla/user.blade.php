@extends('layouts.topnav')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Lifetime Ranking for player: {{ $player->display_name }}</h3>
        </div>

        @if (!$dominions->isEmpty())
            <div class="box-body table-responsive no-padding">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Dominion</th>
                            <th class="text-center">Race</th>
                            <th class="text-center">Realm</th>
                            <th class="text-center">Land</th>
                            <th class="text-center">Networth</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dominions as $dominion)
                            <tr>
                                <td>
                                    {{ $dominion->round->name }}
                                </td>
                                <td>
                                    {{ $dominion->name }}
                                </td>
                                <td class="text-center">
                                    {{ $dominion->race->name }}
                                </td>
                                <td class="text-center">
                                    {{ $dominion->realm->number }}
                                </td>
                                <td class="text-center">
                                    {{ number_format($landCalculator->getTotalLand($dominion)) }}
                                </td>
                                <td class="text-center">
                                    {{ number_format($networthCalculator->getDominionNetworth($dominion)) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="box-body">
                <p>No records found.</p>
            </div>
        @endif
    </div>
@endsection
