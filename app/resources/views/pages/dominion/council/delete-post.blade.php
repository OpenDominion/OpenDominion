@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Post</h3>
        </div>
        <form action="{{ route('dominion.council.delete.post', $post) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">
                Are you sure you want to delete this post?
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Delete Post</button>
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
