@extends('layouts.master')

@section('page-header', 'Valor Breakdown')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-trophy"></i> Valor Breakdown: {{ $realm->name }} (#{{ $realm->number }})</span>
                </div>
                <div class="card-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dominion</th>
                                <th class="text-center" title="Land Rank" data-bs-toggle="tooltip">Rank</th>
                                <th class="text-center" title="Total Land" data-bs-toggle="tooltip">Land</th>
                                <th class="text-center" title="Land Conquered" data-bs-toggle="tooltip">Conquered</th>
                                <th class="text-center" title="Bounties Collected" data-bs-toggle="tooltip">Bounties</th>
                                <th class="text-center" title="War Hits" data-bs-toggle="tooltip">War Hits</th>
                                <th class="text-center" title="Hit of the Round" data-bs-toggle="tooltip">HotR</th>
                                <th class="text-center" title="Wonder Contributions" data-bs-toggle="tooltip">Wonders</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($breakdown as $entry)
                                <tr>
                                    <td>{{ $entry['dominion']->name }}</td>
                                    <td class="text-center">{{ number_format($entry['land_rank'], 1) }}</td>
                                    <td class="text-center">{{ number_format($entry['total_land'], 1) }}</td>
                                    <td class="text-center">{{ number_format($entry['land_conquered'], 1) }}</td>
                                    <td class="text-center">{{ number_format($entry['bounties'], 1) }}</td>
                                    <td class="text-center">{{ number_format($entry['war_hits'], 1) }}</td>
                                    <td class="text-center">{{ number_format($entry['largest_hits'], 1) }}</td>
                                    <td class="text-center">{{ number_format($entry['wonders'], 1) }}</td>
                                    <td class="text-center"><strong>{{ number_format($entry['total'], 1) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Realm Total</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('land_rank'), 1) }}</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('total_land'), 1) }}</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('land_conquered'), 1) }}</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('bounties'), 1) }}</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('war_hits'), 1) }}</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('largest_hits'), 1) }}</th>
                                <th class="text-center">{{ number_format(collect($breakdown)->sum('wonders'), 1) }}</th>
                                <th class="text-center"><strong>{{ number_format(collect($breakdown)->sum('total'), 1) }}</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    <p>Valor is a measure of a realm's overall contribution and prowess during a round. It is the sum of individual dominion valor scores.</p>
                    <p>Valor is composed of <b>fixed</b> and <b>bonus</b> components.</p>

                    <h5>Fixed Valor</h5>
                    <p>Fixed valor is distributed from shared pools based on each dominion's relative contribution:</p>
                    <ul>
                        <li><b>Land Rank</b> &mdash; Based on daily land ranking position. Higher ranks earn more.</li>
                        <li><b>Total Land</b> &mdash; Share of a fixed pool proportional to your total acres.</li>
                        <li><b>Land Conquered</b> &mdash; Share of a fixed pool proportional to total land conquered.</li>
                        <li><b>Bounties</b> &mdash; Share of a fixed pool proportional to bounties collected.</li>
                    </ul>

                    <h5>Bonus Valor</h5>
                    <p>Bonus valor is earned through specific accomplishments:</p>
                    <ul>
                        <li><b>War Hits</b> &mdash; Earned for successful invasions against realms you are at war with.</li>
                        <li><b>HotR</b> &mdash; Earned for achieving the largest hit of the round. Increases in value as the round progresses.</li>
                        <li><b>Wonders</b> &mdash; Earned for contributing damage when your realm destroys a wonder.</li>
                    </ul>

                    <p>Valor is recalculated periodically throughout the round.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
