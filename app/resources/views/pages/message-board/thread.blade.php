@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-wooden-sign"></i> Message Board Thread: {{ $thread->title }}</span>
                    <div class="float-end">
                        <a href="{{ route('message-board.category', $thread->category->slug) }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                @if ($posts->currentPage() == 1)
                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <i class="ra {{ isset($thread->user->settings['boardavatar']) ? $thread->user->settings['boardavatar'] : 'ra-player' }} text-muted flex-shrink-0" style="font-size: 36px; line-height: 1;"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold">
                                    {{ $thread->user->display_name }} {!! $thread->user->displayRoleHtml() !!}
                                </div>
                                <div class="small text-body-secondary">
                                    posted at {{ $thread->created_at }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            @if ($user->hasRole('Administrator'))
                                <a href="{{ route('message-board.thread.edit', $thread) }}" title="Edit Thread"><i class="fa fa-pencil"></i></a>
                            @endif
                            @if ($user->id == $thread->user->id)
                                <a href="{{ route('message-board.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                            @else
                                <a href="{{ route('message-board.flag.thread', $thread) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($thread->flagged_for_removal)
                            <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                        @else
                            {!! Str::markdown($thread->body) !!}
                        @endif
                    </div>
                @else
                    <div class="card-header">
                        <em>Initial post and {{ $posts->perPage() * ($posts->currentPage() - 1) }} replies not shown.</em>
                    </div>
                @endif
                @if (!$posts->isEmpty())
                    <div class="card-footer p-0">
                        @foreach ($posts as $post)
                            <div class="p-3 @if (!$loop->last) border-bottom @endif">
                                <div class="d-flex gap-3">
                                    <i class="ra {{ isset($post->user->settings['boardavatar']) ? $post->user->settings['boardavatar'] : 'ra-player' }} text-muted flex-shrink-0" style="font-size: 26px; line-height: 1;"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                                            <div class="fw-semibold">
                                                {{ $post->user->display_name }} {!! $post->user->displayRoleHtml() !!}
                                            </div>
                                            <div class="small text-body-secondary d-flex align-items-center gap-2 flex-shrink-0">
                                                <span>{{ $post->created_at }}</span>
                                                @if ($user->id == $post->user->id)
                                                    <a href="{{ route('message-board.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                                                @else
                                                    <a href="{{ route('message-board.flag.post', $post) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($post->flagged_for_removal)
                                            <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                                            @include('partials.forum-rules')
                                        @else
                                            {!! Str::markdown($post->body) !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($posts->lastPage() !== 1)
                        <div class="card-footer" style="margin-bottom: -5px;">
                            <div class="text-end">
                                {{ $posts->links() }}
                            </div>
                        </div>
                    @endif
                @endif

                <form action="{{ route('message-board.reply', $thread) }}" method="post" role="form">
                    @csrf
                    <div class="card-footer">
                        <div class="row">
                            <label for="body" class="col-sm-2 col-form-label">Post Reply</label>
                            <div class="col-sm-10">
                                <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required>{{ old('body') }}</textarea>
                                <p class="form-text">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Post Reply</button>
                        <p class="form-text float-end">
                            You are posting as <b>{{ $user->display_name }}</b>.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    <p>The message board is where you can communicate with other players. All registered users can view and post here.</p>
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this category.</p>
                    <p>You may also <a href="{{ route('message-board.avatar') }}">change your avatar</a>.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
