@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Thread: {{ $thread->title }}</h3>
        </div>
        <div class="box-body">
            {!! Markdown::convertToHtml($thread->body) !!}
        </div>
        <div class="box-footer">
            <small><i>Posted {{ $thread->created_at->diffForHumans() }} by {{ $thread->dominion->name }}</i></small>
        </div>
    </div>

    @if (!$thread->posts->isEmpty())
        @foreach ($thread->posts as $post)
            <div class="box box-default">
                <div class="box-body">
                    {!! Markdown::convertToHtml($post->body) !!}
                </div>
                <div class="box-footer">
                    <small><i>Posted {{ $post->created_at->diffForHumans() }} by {{ $post->dominion->name }}</i></small>
                </div>
            </div>
        @endforeach
    @endif

    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Post Reply</h3>
        </div>
        <form action="{{ route('dominion.council.reply', $thread) }}" method="post" class="form-horizontal" role="form">
            <div class="box-body">

                {{-- Body --}}
                <div class="form-group">
                    <label for="body" class="col-sm-3 control-label">Body</label>
                    <div class="col-sm-9">
                        <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required>{{ old('body') }}</textarea>
                        <p class="help-block">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                    </div>
                </div>

            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Post Reply</button>
            </div>
        </form>
    </div>
@endsection
