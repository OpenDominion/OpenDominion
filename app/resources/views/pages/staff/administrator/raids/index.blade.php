@extends('layouts.staff')

@section('page-header', 'Raids')

@section('content')
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">
                Raids - {{ $round->name }}
            </h3>
            <div class="pull-right">
                <a href="{{ route('staff.administrator.raids.create', ['round' => $round->id]) }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Create New Raid
                </a>
                <select id="round-select" class="form-control" style="display: inline-block; width: auto; margin-left: 10px;">
                    @foreach ($rounds as $roundOption)
                        <option value="{{ $roundOption->id }}" {{ $roundOption->id == $round->id ? 'selected' : null }}>
                            {{ $roundOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="box-body table-responsive">
            @if ($raids->isEmpty())
                <p class="text-center text-muted">No raids found for this round.</p>
            @else
                <table class="table table-hover">
                    <colgroup>
                        <col width="50">
                        <col>
                        <col width="150">
                        <col width="150">
                        <col width="100">
                        <col width="100">
                        <col width="150">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Name</th>
                            <th class="text-center">Reward</th>
                            <th class="text-center">Completion Reward</th>
                            <th class="text-center">Start Date</th>
                            <th class="text-center">End Date</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($raids as $raid)
                            <tr>
                                <td class="text-center">{{ $raid->id }}</td>
                                <td>
                                    <a href="{{ route('staff.administrator.raids.show', $raid) }}">
                                        <strong>{{ $raid->name }}</strong>
                                    </a>
                                    <br>
                                    <small class="text-aqua">{{ $raid->objectives->count() }} objective(s)</small>
                                </td>
                                <td class="text-center">
                                    @if ($raid->reward_resource && $raid->reward_amount)
                                        {{ number_format($raid->reward_amount) }} {{ dominion_attr_display($raid->reward_resource, $raid->reward_amount) }}
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($raid->completion_reward_resource && $raid->completion_reward_amount)
                                        {{ number_format($raid->completion_reward_amount) }} {{ dominion_attr_display($raid->completion_reward_resource, $raid->completion_reward_amount) }}
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td class="text-center">Day {{ $round->daysInRound($raid->start_date) }}</td>
                                <td class="text-center">Day {{ $round->daysInRound($raid->end_date) }}</td>
                                <td class="text-center">
                                    {!! $raidHelper->getStatusLabel($raid->status) !!}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
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
            $('#round-select').select2({ width: '225px' }).change(function() {
                var selectedRound = $(this).val();
                window.location.href = "{!! route('staff.administrator.raids.index') !!}/?round=" + selectedRound;
            });
        })(jQuery);
    </script>
@endpush
