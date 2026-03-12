@extends('layouts.master')

@section('page-header', 'Journal')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="ra ra-scroll-quill"></i> Journal</h3>
                </div>
                @if ($selectedJournal !== null)
                    <form action="{{ route('dominion.journal.update', $selectedJournal->id) }}" method="post" class="form" role="form">
                @else
                    <form action="{{ route('dominion.journal.create') }}" method="post" class="form" role="form">
                @endif
                    @csrf
                    <div class="card-body">
                        <textarea
                            name="content"
                            id="content"
                            rows="10"
                            class="form-control"
                            placeholder="A place for your notes, calculations, or round story..."
                            required
                            {{ $selectedJournal == null && $selectedDominion->round->end_date->addDays(7) < now() ? 'disabled' : null }}
                        >{{ $selectedJournal !== null ? $selectedJournal->content : null }}</textarea>
                    </div>

                    <div class="card-footer">
                        <div class="float-end">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->round->end_date->addDays(30) < now() ? 'disabled' : null }}>
                                {{ $selectedJournal !== null ? 'Update Entry' : 'Create Entry' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @foreach ($journals as $journal)
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            Day {{ $journal->daysInRound() }}, Hour {{ $journal->hoursInDay() }}
                        </h3>
                        <div class="card-tools float-end">
                            <a href="{{ route('dominion.journal.delete', $journal->id) }}">
                                <i class="fa fa-trash text-red"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! nl2br($journal->content) !!}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Previous Entries</h3>
                </div>
                <div class="card-body">
                    <ul class="nav nav-stacked">
                        @if ($journals->isEmpty())
                            <li class="table-active">
                                <a>Day {{ $selectedDominion->round->daysInRound() }}, Hour {{ $selectedDominion->round->hoursInDay() }}</a>
                            </li>
                        @else
                            @foreach ($journals as $journal)
                                <li class="{{ $selectedJournal !== null && $journal->id == $selectedJournal->id ? 'active' : null }}">
                                    <a href="{{ route('dominion.journal', $journal->id) }}">
                                        Day {{ $journal->daysInRound() }}, Hour {{ $journal->hoursInDay() }}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
