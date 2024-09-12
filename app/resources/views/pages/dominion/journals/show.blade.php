@extends('layouts.master')

@section('page-header', 'Journal')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-scroll-quill"></i> Journal</h3>
                </div>
                @if ($selectedJournal !== null)
                    <form action="{{ route('dominion.journal.update', $selectedJournal->id) }}" method="post" class="form" role="form">
                @else
                    <form action="{{ route('dominion.journal.create') }}" method="post" class="form" role="form">
                @endif
                    @csrf
                    <div class="box-body">
                        <textarea
                            name="content"
                            id="content"
                            rows="10"
                            class="form-control"
                            placeholder="A place for your notes, calculations, or round story..."
                            required
                            {{ $selectedJournal == null && $selectedDominion->round->hasEnded() ? 'disabled' : null }}
                        >{{ $selectedJournal !== null ? $selectedJournal->content : null }}</textarea>
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->round->hasEnded() ? 'disabled' : null }}>
                                {{ $selectedJournal !== null ? 'Update Entry' : 'Create Entry' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @foreach ($journals as $journal)
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            Day {{ $journal->daysInRound() }}, Hour {{ $journal->hoursInDay() }}
                        </h3>
                        <div class="box-tools pull-right">
                            <a href="{{ route('dominion.journal.delete', $journal->id) }}">
                                <i class="fa fa-trash text-red"></i>
                            </a>
                        </div>
                    </div>
                    <div class="box-body">
                        {{ $journal->content }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Previous Entries</h3>
                </div>
                <div class="box-body">
                    <ul class="nav nav-stacked">
                        @if ($journals->isEmpty())
                            <li class="active">
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
