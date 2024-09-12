@extends('layouts.master')

@section('page-header', 'Journal')

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-scroll-quill"></i> Journal</h3>
                </div>
                <form action="{{ route('dominion.journal.delete', $journal->id) }}" method="post" class="form" role="form">
                    @csrf
                    <div class="box-body">
                        <p>Are you sure you want to delete this journal entry?</p>
                        <b>Day {{ $journal->daysInRound() }}, Hour {{ $journal->hoursInDay() }}</b>
                        <p>{{ $journal->content }}</p>
                    </div>

                    <div class="box-footer">
                        <div class="pull-right">
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
