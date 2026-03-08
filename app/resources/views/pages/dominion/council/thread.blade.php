@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-group"></i> Council Thread: {{ $thread->title }}</h3>
                    <div class="float-end">
                        <a href="{{ route('dominion.council') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                @if ($posts->currentPage() == 1)
                    <div class="card-body">
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
                    <div class="card-body">
                        <em>Initial post and {{ $posts->perPage() * ($posts->currentPage() - 1) }} replies not shown.</em>
                    </div>
                @endif
                @if (!$posts->isEmpty())
                    @foreach ($posts as $post)
                        <div class="card-footer">
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
                        <div class="card-footer" style="margin-bottom: -5px;">
                            <div class="text-end">
                                {{ $posts->links() }}
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Post Reply</h3>
                </div>
                <form action="{{ route('dominion.council.reply', $thread) }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    <div class="card-body">

                        {{-- Body --}}
                        <div class="form-group">
                            <label for="body" class="col-sm-3 control-label">Body</label>
                            <div class="col-sm-9">
                                <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
                                <p class="form-text">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Post Reply</button>
                        <p class="form-text float-end">
                            You are posting as <b>{{ $selectedDominion->name }} ({{ $selectedDominion->user->display_name }})</b>.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Information</h3>
                </div>
                <div class="card-body">
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here.</p>
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                    @include('partials.forum-rules')
                </div>
                <div class="card-body">
                    <p>The council is where you can communicate with the rest of your realm. Only you and other dominions inside your realm can view and post here.</p>
                    {{--<p>Your realm monarch is X and has the power to moderate the council board.</p>--}}
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                </div>
            </div>
            @include('partials.dominion.join-discord')
        </div>

    </div>
@endsection
