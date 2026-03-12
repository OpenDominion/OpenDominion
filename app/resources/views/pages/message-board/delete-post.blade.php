@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Delete Post</h3>
        </div>
        <form action="{{ route('message-board.delete.post', $post) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="card-body">
                Are you sure you want to delete this post?
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-danger">Delete Post</button>
            </div>
        </form>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Thread: {{ $post->thread->title }}</h3>
        </div>
        <div class="card-body">
            {!! Markdown::convertToHtml($post->body) !!}
        </div>
        <div class="card-footer">
            <small>
                <i>
                    Posted {{ $post->created_at }} by
                    <b>{{ $post->user->display_name }}</b>
                </i>
            </small>
        </div>
    </div>
@endsection
