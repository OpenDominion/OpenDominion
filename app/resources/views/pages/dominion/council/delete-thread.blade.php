@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Thread</h3>
        </div>
        <form action="{{ route('dominion.council.delete.thread', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">
                Are you sure you want to delete this thread and all of its contents?
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
                    Posted {{ $thread->created_at }} by <b>{{ $thread->dominion->name }}</b>
                    @if ($thread->dominion->name !== $thread->dominion->ruler_name)
                        ({{ $thread->dominion->ruler_name }})
                    @endif
                </i>
            </small>
        </div>
    </div>

    @if (!$thread->posts->isEmpty())
        @foreach ($thread->posts as $post)
            <div class="box">
                <div class="box-body">
                    {!! Markdown::convertToHtml($post->body) !!}
                </div>
                <div class="box-footer">
                    <small>
                        <i>
                            Posted {{ $post->created_at }} by <b>{{ $post->dominion->name }}</b>
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
