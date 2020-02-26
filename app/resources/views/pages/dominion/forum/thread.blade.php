@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-comments"></i> Forum Thread: {{ $thread->title }}</h3>
            <div class="pull-right">
                <a href="{{ route('dominion.forum') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
            </div>
        </div>
        <div class="box-body">
            @if ($thread->flagged_for_removal)
                <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                @include('partials.forum-rules')
            @else
                {!! Markdown::convertToHtml($thread->body) !!}
            @endif
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
            <div class="pull-right">
                @if ($selectedDominion->id == $thread->dominion->id)
                    <a href="{{ route('dominion.forum.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                @else
                    <a href="{{ route('dominion.forum.flag.thread', $thread) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                @endif
            </div>
        </div>
    </div>

    @if (!$thread->posts->isEmpty())
        @foreach ($thread->posts as $post)
            <div class="box">
                <div class="box-body">
                    @if ($post->flagged_for_removal)
                        <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                        @include('partials.forum-rules')
                    @else
                        {!! Markdown::convertToHtml($post->body) !!}
                    @endif
                </div>
                <div class="box-footer">
                    <small>
                        <i>
                            Posted {{ $post->created_at }} by
                            @if ($post->dominion->isMonarch())
                                <i class="ra ra-queen-crown text-red"></i>
                            @endif
                            <b>{{ $post->dominion->name }}</b> ruler of <b>{{ $post->dominion->ruler_name }}</b>
                            (#{{ $post->dominion->realm->number }})
                        </i>
                    </small>
                    <div class="pull-right">
                        @if ($selectedDominion->id == $post->dominion->id)
                            <a href="{{ route('dominion.forum.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                        @else
                            <a href="{{ route('dominion.forum.flag.post', $post) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Post Reply</h3>
        </div>
        <form action="{{ route('dominion.forum.reply', $thread) }}" method="post" class="form-horizontal" role="form">
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
