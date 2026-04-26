@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">Delete Post</span>
        </div>
        <form action="{{ route('dominion.council.delete.post', $post) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="card-body">
                Are you sure you want to delete this post?
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Delete Post</button>
            </div>
        </form>
    </div>

    <div class="card card-primary">
        <div class="card-header">
            <span class="card-title">Thread: {{ $post->thread->title }}</span>
        </div>
        <div class="card-body">
            {!! Markdown::convertToHtml($post->body) !!}
        </div>
        <div class="card-footer">
            <small>
                <i>
                    Posted {{ $post->created_at }} by
                    @if ($post->dominion->isMonarch())
                        <i class="ra ra-queen-crown text-red"></i>
                    @endif
                    <b>{{ $post->dominion->name }}</b>
                    @if ($post->dominion->name !== $post->dominion->ruler_name)
                        ({{ $post->dominion->ruler_name }})
                    @endif
                </i>
            </small>
        </div>
    </div>
@endsection
