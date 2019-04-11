@extends('layouts.master')

@section('page-header', 'The Realm')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-circle-of-circles"></i> {{ $realm->name }} (#{{ $realm->number }})</h3>
                </div>
                <div class="box-body table-responsive no-padding">

                    <table class="table">
                        <colgroup>
                            <col width="50">
                            <col>
                            @if ($isOwnRealm && $selectedDominion->pack !== null)
                                <col width="200">
                            @endif
                            <col width="100">
                            <col width="100">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Dominion</th>
                                @if ($isOwnRealm && $selectedDominion->pack !== null)
                                    <th class="text-center">Player from Pack</th>
                                @endif
                                <th class="text-center">Race</th>
                                <th class="text-center">Land</th>
                                <th class="text-center">Networth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < $round->realm_size; $i++)
                                @php
                                    $dominion = $dominions->get($i);
                                @endphp

                                @if ($dominion === null)
                                    <tr>
                                        <td>&nbsp;</td>
                                        @if ($isOwnRealm && $selectedDominion->pack !== null)
                                            <td colspan="5"><i>Vacant</i></td>
                                        @else
                                            <td colspan="4"><i>Vacant</i></td>
                                        @endif
                                    </tr>
                                @else
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            @if ($protectionService->isUnderProtection($dominion))
                                                <i class="ra ra-shield ra-lg text-aqua" title="Under protection"></i>
                                            @endif

                                            {{--

                                            RG: <i class="ra ra-crossed-swords ra-lg text-green"></i>
                                            EG: <i class="ra ra-crossed-swords ra-lg text-yellow"></i>
                                            Monarch: <i class="ra ra-queen-crown ra-lg"></i>
                                                RG: text-green
                                                EG: text-yellow

                                            --}}

                                            @if ($dominion->id === $selectedDominion->id)
                                                <b>{{ $dominion->name }}</b> (you)
                                            @else
                                                {{ $dominion->name }}
                                            @endif

                                            @if ($isOwnRealm && $dominion->round->isActive() && $dominion->user->isOnline())
                                                <span class="label label-success">Online</span>
                                            @endif
                                        </td>
                                        @if ($isOwnRealm && $selectedDominion->pack !== null)
                                            @if (($dominion->pack !== null) && ($dominion->pack->id === $selectedDominion->pack->id))
                                                <td class="text-center">{{ $dominion->ruler_name }}</td>
                                            @else
                                                <td class="text-center"></td>
                                            @endif
                                        @endif
                                        <td class="text-center">
                                            {{ $dominion->race->name }}
                                            {{--
                                            todo: fix above statement which generates this query:
                                            select * from "units" where "units"."race_id" = '1' and "units"."race_id" is not null order by "slot" asc limit 4
                                            --}}
                                        </td>
                                        <td class="text-center">{{ number_format($landCalculator->getTotalLand($dominion)) }}</td>
                                        <td class="text-center">{{ number_format($networthCalculator->getDominionNetworth($dominion)) }}</td>
                                    </tr>
                                @endif
                            @endfor
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This is the realm <strong>{{ $realm->name }} (#{{ $realm->number }})</strong>.</p>
                    <p>Its alignment is <strong>{{ $realm->alignment }}</strong>, it contains <strong>{{ $dominions->count() }}</strong> {{ str_plural('dominion', $dominions->count()) }} and its networth is <strong>{{ number_format($networthCalculator->getRealmNetworth($realm)) }}</strong>.</p>
                    {{-- todo: change this to a table? --}}
                    <p><a href="{{ route('dominion.realm') }}">My Realm</a></p>
                </div>
                @if (($prevRealm !== null) || ($nextRealm !== null))
                    <div class="box-footer">
                        <div class="row">
                            <div class="col-xs-4">
                                @if ($prevRealm !== null)
                                    <a href="{{ route('dominion.realm', $prevRealm->number) }}">&lt; Previous</a><br>
                                    <small class="text-muted">{{ $prevRealm->name }} (# {{  $prevRealm->number }})</small>
                                @endif
                            </div>
                            <div class="col-xs-4">
                                <form action="{{ route('dominion.realm.change-realm') }}" method="post" role="form">
                                    @csrf
                                    <input type="number" name="realm" class="form-control text-center" placeholder="{{ $realm->number }}">
                                </form>
                            </div>
                            <div class="col-xs-4 text-right">
                                @if ($nextRealm !== null)
                                    <a href="{{ route('dominion.realm', $nextRealm->number) }}">Next &gt;</a><br>
                                    <small class="text-muted">{{ $nextRealm->name }} (# {{  $nextRealm->number }})</small>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection
