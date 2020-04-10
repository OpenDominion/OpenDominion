@extends('layouts.master')

@section('page-header', 'Rankings')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <span class="pull-right">
                        @if ($type === 'largest-dominions')
                            <b>Land</b> - <a href="{{ route('dominion.rankings', ['strongest-dominions'] + Request::query()) }}">Networth</a>
                        @else
                            <a href="{{ route('dominion.rankings', ['largest-dominions'] + Request::query()) }}">Land</a> - <b>Networth</b>
                        @endif
                    </span>
                    <h3 class="box-title"><i class="fa fa-trophy"></i> Rankings</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col width="50">
                            <col>
                            <col width="150">
                            <col width="100">
                            <col width="100">
                            <col width="50">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Dominion</th>
                                <th class="text-center">Realm</th>
                                <th class="text-center">Race</th>
                                <th class="text-center">Value</th>
                                <th class="text-center">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rankings as $row)
                                <tr>
                                    <td class="text-center">{{ $row->rank }}</td>
                                    <td>
                                        @if ($selectedDominion->id === (int)$row->dominion_id)
                                            <b>{{ $row->dominion_name }}</b> (you)
                                        @else
                                            {{ $row->dominion_name }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('dominion.realm', $row->realm_number) }}">{{ $row->realm_name }} (#{{ $row->realm_number }})</a>
                                    </td>
                                    <td class="text-center">{{ $row->race_name }}</td>
                                    <td class="text-center">{{ number_format($row->value) }}</td>
                                    <td class="text-center">
                                        @php
                                            $rankChange = (int) ($row->previous_rank - $row->rank);
                                        @endphp
                                        @if ($rankChange > 0)
                                            <span class="text-success"><i class="fa fa-caret-up"></i> {{ $rankChange }}</span>
                                        @elseif ($rankChange === 0)
                                            <span class="text-warning">-</span>
                                        @else
                                            <span class="text-danger"><i class="fa fa-caret-down"></i> {{ abs($rankChange) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        {{ $rankings->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>This page shows you the rankings of all the dominions in this round.</p>
                    <p>Rankings are updated every 6 hours.</p>
                    @if (!empty($rankings))
                        @php
                            $rankingsUpdatedHoursAgo = (now()->hour % 6);
                        @endphp
                        @if ($rankingsUpdatedHoursAgo === 0)
                            <p>Current displayed rankings are from this hour.</p>
                        @else
                            <p>Current displayed rankings are from {{ $rankingsUpdatedHoursAgo }} {{ str_plural('hour', $rankingsUpdatedHoursAgo) }} ago.</p>
                        @endif
                    @endif
                    <p><a href="{{ route('dominion.rankings', request('type')) }}">My Ranking</a></p>
                </div>
            </div>
        </div>

    </div>
@endsection
