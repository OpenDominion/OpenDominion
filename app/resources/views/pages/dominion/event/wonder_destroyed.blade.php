@extends('layouts.master')

@section('page-header', 'Wonder Destroyed')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-10 offset-md-1">
            <div class="card border-success">
                <div class="card-header">
                    <span class="card-title">
                        <i class="ra ra-sword"></i>
                        {{ $event->source->wonder->name }} — Destroyed
                        @if ($event->target !== null)
                            by {{ $event->target->name }} (#{{ $event->target->number }})
                        @endif
                    </span>
                </div>
                <div class="card-body no-padding">
                    @if (isset($event->data['damageBreakdown']) && count($event->data['damageBreakdown']) > 0)
                        <div class="table-responsive">
                        <table class="table table-striped">
                            <colgroup>
                                <col>
                                <col width="12%">
                                <col width="12%">
                                <col width="12%">
                                <col width="8%">
                                <col width="10%">
                                <col width="10%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Dominion</th>
                                    <th class="text-center">Attack</th>
                                    <th class="text-center">Cyclone</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">%</th>
                                    <th class="text-center">Prestige</th>
                                    <th class="text-center">Mastery</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($event->data['damageBreakdown'] as $entry)
                                    <tr>
                                        <td>
                                            {{ $entry['dominion_name'] }}
                                            (#{{ $entry['realm_number'] }})
                                        </td>
                                        <td class="text-center">{{ number_format($entry['damage_attack']) }}</td>
                                        <td class="text-center">{{ number_format($entry['damage_cyclone']) }}</td>
                                        <td class="text-center">{{ number_format($entry['damage_total']) }}</td>
                                        <td class="text-center">
                                            @if ($event->data['totalDamage'] > 0)
                                                {{ number_format($entry['damage_total'] / $event->data['totalDamage'] * 100, 1) }}%
                                            @else
                                                0%
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($entry['prestige']) }}</td>
                                        <td class="text-center">{{ number_format($entry['mastery']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td>Total</td>
                                    <td class="text-center">{{ number_format(array_sum(array_column($event->data['damageBreakdown'], 'damage_attack'))) }}</td>
                                    <td class="text-center">{{ number_format(array_sum(array_column($event->data['damageBreakdown'], 'damage_cyclone'))) }}</td>
                                    <td class="text-center">{{ number_format($event->data['totalDamage']) }}</td>
                                    <td class="text-center">100%</td>
                                    <td class="text-center">{{ number_format(array_sum(array_column($event->data['damageBreakdown'], 'prestige'))) }}</td>
                                    <td class="text-center">{{ number_format(array_sum(array_column($event->data['damageBreakdown'], 'mastery'))) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    @else
                        <div class="card-body">
                            <p class="text-center">No damage breakdown available for this event.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
