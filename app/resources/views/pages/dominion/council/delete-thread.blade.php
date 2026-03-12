@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Delete Thread</h3>
        </div>
        <form action="{{ route('dominion.council.delete.thread', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="card-body">
                Are you sure you want to delete this thread and all of its contents?
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Delete Thread</button>
            </div>
        </form>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Thread: {{ $thread->title }}</h3>
        </div>
        <div class="card-body">
            {!! Markdown::convertToHtml($thread->body) !!}
        </div>
        <div class="card-footer">
            <small>
                <i>
                    Posted {{ $thread->created_at }} by
                    @if ($thread->dominion->isMonarch())
                        <i class="ra ra-queen-crown text-red"></i>
                    @endif
                    <b>{{ $thread->dominion->name }}</b>
                    @if ($thread->dominion->name !== $thread->dominion->ruler_name)
                        ({{ $thread->dominion->ruler_name }})
                    @endif
                </i>
            </small>
        </div>
    </div>

    @if (!$thread->posts->isEmpty())
        @foreach ($thread->posts as $post)
            <div class="card">
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
        @endforeach
    @endif
@endsection
