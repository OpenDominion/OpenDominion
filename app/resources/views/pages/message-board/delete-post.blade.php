@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Post</h3>
        </div>
        <form action="{{ route('message-board.delete.post', $post) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">
                Are you sure you want to delete this post?
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-danger">Delete Post</button>
            </div>
        </form>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Thread: {{ $post->thread->title }}</h3>
        </div>
        <div class="box-body">
            {!! Markdown::convertToHtml($post->body) !!}
        </div>
        <div class="box-footer">
            <small>
                <i>
                    Posted {{ $post->created_at }} by
                    <b>{{ $post->user->display_name }}</b>
                </i>
            </small>
        </div>
    </div>
@endsection
