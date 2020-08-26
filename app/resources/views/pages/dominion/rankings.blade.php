@extends('layouts.master')

@section('page-header', 'Rankings')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-trophy"></i> Rankings
                    </h3>
                    <select id="ranking-select" class="form-control pull-right">
                        @foreach ($rankings as $ranking)
                            <option value="{{ $ranking['key'] }}" {{ $type == $ranking['key'] ? 'selected' : null }}>
                                {{ $ranking['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table">
                        <colgroup>
                            <col width="50">
                            <col>
                            <col width="150">
                            <col width="100">
                            <col>
                            <col width="50">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Dominion</th>
                                <th class="text-center">Realm</th>
                                <th class="text-center">Race</th>
                                <th class="text-center">{{ $rankings[$type]['stat_label'] }}</th>
                                <th class="text-center">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daily_rankings as $row)
                                <tr>
                                    <td class="text-center">{{ $row->rank }}</td>
                                    <td>
                                        @if ($row->rank == 1)
                                            <i class="ra {{ $rankings[$type]['title_icon'] ? $rankings[$type]['title_icon'] : 'ra-trophy' }}" data-toggle="tooltip" title="{{ $rankings[$type]['title'] }}"></i>
                                        @endif
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
                        {{ $daily_rankings->links() }}
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
                    <p>This page shows you the rankings of all dominions in this round and is updated every 24 hours starting on the 5th day of the round.</p>
                    @if (!empty($daily_rankings) && $selectedDominion->round->start_date <= now()->subDays(4))
                        @php
                            $rankingsUpdatedHoursAgo = now()->diffInHours($selectedDominion->round->start_date) % 24;
                        @endphp
                        @if ($rankingsUpdatedHoursAgo === 0)
                            <p>Current displayed rankings were updated this hour.</p>
                        @else
                            <p>Current displayed rankings were updated {{ $rankingsUpdatedHoursAgo }} {{ str_plural('hour', $rankingsUpdatedHoursAgo) }} ago.</p>
                        @endif
                    @endif
                    <p><a href="{{ route('dominion.advisors.rankings') }}">My Rankings</a></p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#ranking-select').select2({ width: '225px' }).change(function() {
                var selectedRanking = $(this).val();
                window.location.href = "{!! route('dominion.rankings') !!}/" + selectedRanking;
            });
            $('#ranking-select + .select2-container').addClass('pull-right');
        })(jQuery);
    </script>
@endpush
