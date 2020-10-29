@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Thread</h3>
        </div>
        <form action="{{ route('message-board.delete.thread', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">
                @if ($thread->posts->isEmpty())
                    Are you sure you want to delete this thread and all of its contents?
                @else
                    Are you sure you want to delete the content of this initial post?
                @endif
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-danger">Delete Thread</button>
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
                    <b>{{ $thread->user->display_name }}</b>
                </i>
            </small>
        </div>
    </div>
@endsection
