@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-group"></i> Council Thread: {{ $thread->title }}</h3>
                    <div class="pull-right">
                        <a href="{{ route('dominion.council') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                @if ($posts->currentPage() == 1)
                    <div class="box-body">
                        {!! Markdown::convertToHtml($thread->body) !!}
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
                        @if ($selectedDominion->isMonarch() || ($thread->posts->isEmpty() && $selectedDominion->id == $thread->dominion->id))
                            <a href="{{ route('dominion.council.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                        @endif
                    </div>
                @else
                    <div class="box-body">
                        <em>Initial post and {{ $posts->perPage() * ($posts->currentPage() - 1) }} replies not shown.</em>
                    </div>
                @endif
                @if (!$posts->isEmpty())
                    @foreach ($posts as $post)
                        <div class="box-footer">
                            {!! Markdown::convertToHtml($post->body) !!}
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
                            @if ($selectedDominion->isMonarch() || $selectedDominion->id == $post->dominion->id)
                                <a href="{{ route('dominion.council.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                            @endif
                        </div>
                    @endforeach
                    @if ($posts->lastPage() !== 1)
                        <div class="box-footer" style="margin-bottom: -5px;">
                            <div class="text-right">
                                {{ $posts->links() }}
                            </div>
                        </div>
                    @endif
                @endif
            </div>

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
                        <p class="help-block pull-right">
                            You are posting as <b>{{ $selectedDominion->name }} ({{ $selectedDominion->user->display_name }})</b>.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here.</p>
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                    @include('partials.forum-rules')
                </div>
                <div class="box-body">
                    <p>The council is where you can communicate with the rest of your realm. Only you and other dominions inside your realm can view and post here.</p>
                    {{--<p>Your realm monarch is X and has the power to moderate the council board.</p>--}}
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
