@extends('layouts.staff')

@section('page-header', 'Hero Tournaments')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                Hero Tournaments - {{ $round->name }}
            </span>
            <div class="float-end">
                <a href="{{ route('staff.administrator.hero-tournaments.create', ['round' => $round->id]) }}" class="btn btn-success">
                    <i class="fa fa-plus"></i> Create New Tournament
                </a>
                <select id="round-select" class="form-select" style="display: inline-block; width: auto; margin-left: 10px;">
                    @foreach ($rounds as $roundOption)
                        <option value="{{ $roundOption->id }}" {{ $roundOption->id == $round->id ? 'selected' : null }}>
                            {{ $roundOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body table-responsive">
            @if ($tournaments->isEmpty())
                <p class="text-center text-muted">No hero tournaments found for this round.</p>
            @else
                <table class="table table-hover">
                    <colgroup>
                        <col width="50">
                        <col>
                        <col width="120">
                        <col width="120">
                        <col width="120">
                        <col width="120">
                        <col width="100">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Name</th>
                            <th class="text-center">Start Day</th>
                            <th class="text-center">Participants</th>
                            <th class="text-center">Round #</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tournaments as $tournament)
                            <tr>
                                <td class="text-center">{{ $tournament->id }}</td>
                                <td>
                                    <a href="{{ route('staff.administrator.hero-tournaments.show', $tournament) }}">
                                        <strong>{{ $tournament->name }}</strong>
                                    </a>
                                    @if ($tournament->winner)
                                        <br>
                                        <small class="text-aqua">Winner: {{ $tournament->winner->name }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($tournament->start_date)
                                        Day {{ $round->daysInRound($tournament->start_date) }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $tournament->participants->count() }}</td>
                                <td class="text-center">{{ $tournament->current_round_number }}</td>
                                <td class="text-center">
                                    @if ($tournament->finished)
                                        <span class="badge text-bg-secondary">Finished</span>
                                    @elseif ($tournament->hasStarted())
                                        <span class="badge text-bg-success">In Progress</span>
                                    @elseif ($tournament->start_date)
                                        <span class="badge text-bg-info">Registration Open</span>
                                    @else
                                        <span class="badge text-bg-warning">Draft</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('staff.administrator.hero-tournaments.show', $tournament) }}" class="btn btn-sm btn-primary" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="{{ route('staff.administrator.hero-tournaments.edit', $tournament) }}" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <a href="{{ route('staff.administrator.hero-tournaments.delete', $tournament) }}" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('#round-select').select2({ width: '225px' }).change(function() {
                var selectedRound = $(this).val();
                window.location.href = "{!! route('staff.administrator.hero-tournaments.index') !!}/?round=" + selectedRound;
            });
        })(jQuery);
    </script>
@endpush
