@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-comments"></i> Round Forum Thread: {{ $thread->title }}</h3>
                    <div class="pull-right">
                        <a href="{{ route('dominion.forum') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                @if ($posts->currentPage() == 1)
                    <div class="box-header with-border">
                        <div class="user-block pull-left">
                            @php
                                $rankings = $rankingsService->getTopRankedDominions($thread->dominion->round);
                                $titles = isset($rankings[$thread->dominion->id]) ? $rankings[$thread->dominion->id] : [];
                                $ranking = $rankingsHelper->getFirstRanking($titles);
                            @endphp
                            <i class="ra {{ $ranking && $ranking['title_icon'] ? $ranking['title_icon'] : 'ra-knight-helmet' }} text-muted pull-left" title="{{ $ranking ? $ranking['name'] : null }}" style="font-size: 36px;"></i>
                            <span class="username">
                                {{ $thread->dominion->name }} (#{{ $thread->dominion->realm->number }})
                                @if ($ranking && $ranking['title'])
                                    <em data-toggle="tooltip" title="{{ $ranking['name'] }}">{{ $ranking['title'] }}</em>
                                @endif
                            </span>
                            <span class="description">
                                posted at {{ $thread->created_at }}
                            </span>
                        </div>
                        <div class="box-tools">
                            @if ($selectedDominion->id == $thread->dominion->id)
                                <a href="{{ route('dominion.forum.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                            @else
                                <a href="{{ route('dominion.forum.flag.thread', $thread) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="box-body">
                        @if ($thread->flagged_for_removal)
                            <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                        @else
                            {!! Markdown::convertToHtml($thread->body) !!}
                        @endif
                    </div>
                @else
                    <div class="box-header with-border">
                        <em>Initial post and {{ $posts->perPage() * ($posts->currentPage() - 1) }} replies not shown.</em>
                    </div>
                @endif
                @if (!$posts->isEmpty())
                    <div class="box-footer box-comments">
                        @foreach ($posts as $post)
                            <div class="box-comment">
                                @php
                                    $rankings = $rankingsService->getTopRankedDominions($post->dominion->round);
                                    $titles = isset($rankings[$post->dominion->id]) ? $rankings[$post->dominion->id] : [];
                                    $ranking = $rankingsHelper->getFirstRanking($titles);
                                @endphp
                                <i class="ra {{ $ranking && $ranking['title_icon'] ? $ranking['title_icon'] : 'ra-knight-helmet' }} text-muted pull-left" title="{{ $ranking ? $ranking['name'] : null }}" style="font-size: 26px;"></i>
                                <div class="comment-text">
                                    <span class="username">
                                        {{ $post->dominion->name }} (#{{ $post->dominion->realm->number }})
                                        @if ($ranking && $ranking['title'])
                                            <em data-toggle="tooltip" title="{{ $ranking['name'] }}">{{ $ranking['title'] }}</em>
                                        @endif
                                        <span class="text-muted pull-right">
                                            {{ $post->created_at }}&nbsp;
                                            @if ($selectedDominion->id == $post->dominion->id)
                                                <a href="{{ route('dominion.forum.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                                            @else
                                                <a href="{{ route('dominion.forum.flag.post', $post) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                                            @endif
                                        </span>
                                    </span>
                                    @if ($post->flagged_for_removal)
                                        <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                                        @include('partials.forum-rules')
                                    @else
                                        {!! Markdown::convertToHtml($post->body) !!}
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($posts->lastPage() !== 1)
                        <div class="box-footer" style="margin-bottom: -5px;">
                            <div class="text-right">
                                {{ $posts->links() }}
                            </div>
                        </div>
                    @endif
                @endif

                <form action="{{ route('dominion.forum.reply', $thread) }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    <div class="box-footer">
                        <label for="body" class="col-sm-2 control-label">Post Reply</label>
                        <div class="col-sm-10">
                            <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
                            <p class="help-block">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                        </div>
                    </div>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Post Reply</button>
                        <p class="help-block pull-right">
                            You are posting as <b>{{ $selectedDominion->name }} (#{{ $selectedDominion->realm->number }})</b>.
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
            </div>
        </div>

    </div>
@endsection
