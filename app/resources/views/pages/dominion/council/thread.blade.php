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
            <small>
                <i>
                    Posted {{ $thread->created_at->diffForHumans() }} by <b>{{ $thread->dominion->name }}</b>
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
                            Posted {{ $post->created_at->diffForHumans() }} by <b>{{ $post->dominion->name }}</b>
                            @if ($post->dominion->name !== $post->dominion->ruler_name)
                                ({{ $post->dominion->ruler_name }})
                            @endif
                        </i>
                    </small>
                </div>
            </div>
        @endforeach
    @endif

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Post Reply</h3>
        </div>
        <form action="{{ route('dominion.council.reply', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="box-body">

                {{-- Body --}}
                <div class="form-group">
                    <label for="body" class="col-sm-3 control-label">Body</label>
                    <div class="col-sm-9">
                        <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
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
