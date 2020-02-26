@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Thread</h3>
        </div>
        <form action="{{ route('dominion.forum.delete.thread', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">
                @if ($thread->posts->isEmpty())
                    Are you sure you want to delete this thread and all of its contents?
                @else
                    Are you sure you want to delete the content of this initial post?
                @endif
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Delete Thread</button>
            </div>
        </form>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Thread: {{ $thread->title }}</h3>
        </div>
        <div class="box-body">
            {!! Markdown::convertToHtml($thread->body) !!}
        </div>
        <div class="box-footer">
            <small>
                <i>
                    Posted {{ $thread->created_at }} by
                    @if ($thread->dominion->isMonarch())
                        <i class="ra ra-queen-crown text-red"></i>
                    @endif
                    <b>{{ $thread->dominion->ruler_name }}</b> ruler of <b>{{ $thread->dominion->name }}</b>
                    (#{{ $thread->dominion->realm->number }})
                </i>
            </small>
        </div>
    </div>
@endsection
