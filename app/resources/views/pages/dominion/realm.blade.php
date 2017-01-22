@extends('layouts.master')

@section('page-header', 'The Realm')

@section('content')
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-circle-of-circles"></i> Realm '{{ $realm->name }}' (#{{ $realm->number }}). Alignment: {{ $realm->alignment }}. Networth: {{ $networthCalculator->getRealmNetworth($realm) }}</h3>
        </div>
        <div class="box-body no-padding">

            <table class="table">
                <colgroup>
                    <col>
                    <col width="100">
                    <col width="100">
                    <col width="100">
                </colgroup>
                <thead>
                    <tr>
                        <th>Dominion</th>
                        <th class="text-center">Race</th>
                        <th class="text-center">Land</th>
                        <th class="text-center">Networth</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < 12; $i++)
                        @php
                        $dominion = $dominions->get($i);

                        if ($dominion !== null) {
                            $landCalculator->setDominion($dominion);
                        }
                        @endphp

                        @if ($dominion === null)
                            <tr>
                                <td colspan="4"><i>Vacant</i></td>
                            </tr>
                        @else
                            <tr>
                                <td>
                                    @if ($dominion->id !== $selectedDominion->id)
                                        <a href="{{ route('dominion.other.status', $dominion->id) }}">{{ $dominion->name }}</a>
                                    @else
                                        <b><a href="{{ route('dominion.status') }}">{{ $dominion->name }}</a></b> (you)
                                    @endif
                                </td>
                                <td class="text-center">{{ $dominion->race->name }}</td>
                                <td class="text-center">{{ $landCalculator->getTotalLand() }}</td>
                                <td class="text-center">{{ $dominion->networth }}</td>
                            </tr>
                        @endif
                    @endfor
                </tbody>
            </table>

        </div>
    </div>
@endsection
