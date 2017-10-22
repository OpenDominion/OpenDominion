@extends('layouts.master')

@section('page-header', 'Rankings Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-trophy"></i> Rankings Advisor</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table">
                        <colgroup>
                            <col width="50">
                            <col>
                            <col width="100">
                            <col width="150">
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Dominion</th>
                                <th class="text-center">Race</th>
                                <th class="text-center">Realm</th>
                                <th class="text-center">{{ ucfirst($type) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rankings as $row)
                                <tr>
                                    <td class="text-center">{{ $row->{$type . '_rank'} }}</td>
                                    <td>
                                        @if ($selectedDominion->id === (int)$row->dominion_id)
                                            <b>{{ $row->dominion_name }}</b> (you)
                                        @else
                                            {{ $row->dominion_name }}
                                        @endif

                                    </td>
                                    <td class="text-center">{{ $row->race_name }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('dominion.realm', $row->realm_number) }}">{{ $row->realm_name }} (#{{ $row->realm_number }})</a>
                                    </td>
                                    <td class="text-center">{{ number_format($row->$type) }}</td>
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

        <div class="col-md-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The rankings advisor tells you how well all the dominions are doing in the world.</p>
                    {{--<p><a href="#">My ranking</a></p>--}}
                </div>
            </div>
        </div>

    </div>
@endsection
