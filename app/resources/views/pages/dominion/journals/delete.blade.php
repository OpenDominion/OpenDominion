@extends('layouts.master')

@section('page-header', 'Journal')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-scroll-quill"></i> Journal</span>
                </div>
                <form action="{{ route('dominion.journal.delete', $journal->id) }}" method="post" class="form" role="form">
                    @csrf
                    <div class="card-body">
                        <p>Are you sure you want to delete this journal entry?</p>
                        <b>Day {{ $journal->daysInRound() }}, Hour {{ $journal->hoursInDay() }}</b>
                        <p>{{ $journal->content }}</p>
                    </div>

                    <div class="card-footer">
                        <div class="float-end">
                            <button type="submit" class="btn btn-danger">
                                Delete Entry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
