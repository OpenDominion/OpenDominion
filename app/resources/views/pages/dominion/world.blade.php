@extends('layouts.master')

@section('page-header', 'The World')

@section('content')
    <div class="row">

        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-globe"></i> The World</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col width="5%">
                            <col>
                            <col width="10%">
                            <col width="20%">
                            <col width="10%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Realm</th>
                                <th>Wars</th>
                                <th>Wonder</th>
                                <th>Land</th>
                                <th>Networth</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($realms as $realm)
                                <tr>
                                    <td>
                                        {{ $realm->number }}
                                    </td>
                                    <td>
                                        <a href="{{ route('dominion.realm', $realm->number) }}">
                                            {{ $realm->name }}
                                        </a>
                                    </td>
                                    <td>
                                        @if (!$realm->warsIncoming->isEmpty() || !$realm->warsOutgoing->isEmpty())
                                            @if (!$realm->warsIncoming->isEmpty() && !$realm->warsOutgoing->isEmpty())
                                                <i class="ra ra-crossed-axes ra-lg" style="display: inline-block; margin-right: 10px;"></i>
                                                {{ $realm->warsIncoming->map(function ($war) { return $war->sourceRealm->number; })->merge($realm->warsOutgoing->map(function ($war) { return $war->targetRealm->number; }))->unique()->implode(', ') }}
                                            @elseif (!$realm->warsIncoming->isEmpty())
                                                <i class="ra ra-axe-swing ra-lg" style="display: inline-block; margin-right: 10px;"></i>
                                                {{ $realm->warsIncoming->map(function ($war) { return $war->sourceRealm->number; })->implode(', ') }}
                                            @else
                                                <i class="ra ra-axe-swing  ra-lg ra-flip-horizontal" style="display: inline-block; margin-right: 10px;"></i>
                                                {{ $realm->warsOutgoing->map(function ($war) { return $war->targetRealm->number; })->implode(', ') }}
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if (!$realm->wonders->isEmpty())
                                            @foreach ($realm->wonders as $wonder)
                                                <a href="{{ route('dominion.wonders') }}">
                                                    {{ $wonder->name }}
                                                </a>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($realm->dominions->map(function($dominion) use ($landCalculator) { return $landCalculator->getTotalLand($dominion); })->sum()) }}
                                    </td>
                                    <td>
                                        {{ number_format($networthCalculator->getRealmNetworth($realm)) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
