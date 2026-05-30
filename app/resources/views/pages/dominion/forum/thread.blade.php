@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-comments"></i> Round Forum Thread: {{ $thread->title }}</span>
                    <div class="float-end">
                        <a href="{{ route('dominion.forum') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                @if ($posts->currentPage() == 1)
                    @php
                        $rankings = $rankingsService->getTopRankedDominions($thread->dominion->round);
                        $titles = isset($rankings[$thread->dominion->id]) ? $rankings[$thread->dominion->id] : [];
                        $ranking = $rankingsHelper->getFirstRanking($titles, isset($thread->dominion->settings['preferred_title']) ? $thread->dominion->settings['preferred_title'] : '');
                    @endphp
                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <i class="ra {{ $ranking && $ranking['title_icon'] ? $ranking['title_icon'] : 'ra-knight-helmet' }} text-muted flex-shrink-0" title="{{ $ranking ? $ranking['name'] : null }}" style="font-size: 36px; line-height: 1;"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold">
                                    {{ $thread->dominion->name }} (#{{ $thread->dominion->realm->number }})
                                    @if ($ranking && $ranking['title'])
                                        <em data-bs-toggle="tooltip" title="{{ $ranking['name'] }}">{{ $ranking['title'] }}</em>
                                    @endif
                                </div>
                                <div class="small text-body-secondary">
                                    posted at {{ $thread->created_at }}
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            @if ($selectedDominion->id == $thread->dominion->id)
                                <a href="{{ route('dominion.forum.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                            @else
                                <a href="{{ route('dominion.forum.flag.thread', $thread) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($thread->flagged_for_removal)
                            <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                        @else
                            {!! Str::markdown($thread->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
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
                            @php
                                $rankings = $rankingsService->getTopRankedDominions($post->dominion->round);
                                $titles = isset($rankings[$post->dominion->id]) ? $rankings[$post->dominion->id] : [];
                                $ranking = $rankingsHelper->getFirstRanking($titles, isset($post->dominion->settings['preferred_title']) ? $post->dominion->settings['preferred_title'] : '');
                            @endphp
                            <div class="p-3 @if (!$loop->last) border-bottom @endif">
                                <div class="d-flex gap-3">
                                    <i class="ra {{ $ranking && $ranking['title_icon'] ? $ranking['title_icon'] : 'ra-knight-helmet' }} text-muted flex-shrink-0" title="{{ $ranking ? $ranking['name'] : null }}" style="font-size: 26px; line-height: 1;"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                                            <div class="fw-semibold">
                                                {{ $post->dominion->name }} (#{{ $post->dominion->realm->number }})
                                                @if ($ranking && $ranking['title'])
                                                    <em data-bs-toggle="tooltip" title="{{ $ranking['name'] }}">{{ $ranking['title'] }}</em>
                                                @endif
                                            </div>
                                            <div class="small text-body-secondary d-flex align-items-center gap-2 flex-shrink-0">
                                                <span>{{ $post->created_at }}</span>
                                                @if ($selectedDominion->id == $post->dominion->id)
                                                    <a href="{{ route('dominion.forum.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                                                @else
                                                    <a href="{{ route('dominion.forum.flag.post', $post) }}" title="Report Abuse"><i class="fa fa-flag text-red"></i></a>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($post->flagged_for_removal)
                                            <p class="text-danger"><i>This post has been flagged for removal.</i></p>
                                            @include('partials.forum-rules')
                                        @else
                                            {!! Str::markdown($post->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
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

                <form action="{{ route('dominion.forum.reply', $thread) }}" method="post" role="form">
                    @csrf
                    <div class="card-footer">
                        <div class="row">
                            <label for="body" class="col-sm-2 col-form-label">Post Reply</label>
                            <div class="col-sm-10">
                                <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
                                <p class="form-text">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Post Reply</button>
                        <p class="form-text float-end">
                            You are posting as <b>{{ $selectedDominion->name }} (#{{ $selectedDominion->realm->number }})</b>.
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
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here. You may also <a href="{{ route('dominion.misc.settings') }}">select your title</a>.</p>
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
