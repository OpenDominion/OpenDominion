@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-comments"></i> Round Forum: {{ $round->name }}</h3>
                </div>
                <div class="box-body">
                    <table class="table table-hover">
                        <colgroup>
                            <col>
                            <col width="10%">
                            <col width="25%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Topics</th>
                                <th class="text-center">Replies</th>
                                <th class="text-center">Last Reply</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($protectionService->isUnderProtection($selectedDominion))
                                <tr>
                                    <td colspan="3">
                                        You are currently under protection for
                                        @if ($protectionService->getUnderProtectionHoursLeft($selectedDominion))
                                            <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours
                                        @else
                                            <b>{{ $selectedDominion->protection_ticks_remaining }}</b> ticks
                                        @endif
                                        and may not access the forum during that time.
                                    </td>
                                </tr>
                            @else
                                @if (!$forumThreads->isEmpty())
                                    @foreach ($forumThreads as $thread)
                                        <tr>
                                            <td class="align-middle">
                                                <a href="{{ route('dominion.forum.thread', $thread) }}" class="{{ $thread->last_activity > $lastRead ? 'text-bold' : null }}">
                                                    {{ $thread->title }}
                                                </a>
                                                @php
                                                    $pageCount = ceil($thread->posts->count() / $resultsPerPage);
                                                @endphp
                                                @if ($pageCount > 1)
                                                    <span class="small" style="margin-left: 10px;">
                                                        @foreach (range(1, $pageCount) as $page)
                                                            <a href="{{ route('dominion.forum.thread', $thread) }}?page={{ $page }}"><span class="label label-primary">{{ $page }}</span></a>
                                                        @endforeach
                                                    </span>
                                                @endif
                                                <br>
                                                <small class="text-muted">
                                                    Created {{ $thread->created_at }} by
                                                    <b>{{ $thread->dominion->name }}</b>
                                                    (#{{ $thread->dominion->realm->number }})
                                                </small>
                                            </td>
                                            <td class="text-center align-middle">
                                                {{ number_format($thread->posts->count()) }}
                                            </td>
                                            <td class="text-center align-middle">
                                                @if (!$thread->posts->isEmpty())
                                                    {{ $thread->latestPost->created_at }}<br>
                                                    <small class="text-muted">
                                                        by
                                                        <b>{{ $thread->latestPost->dominion->name }}</b>
                                                        (#{{ $thread->latestPost->dominion->realm->number }})
                                                    </small>
                                                @else
                                                    None
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="text-center" colspan="3">No threads found</td>
                                    </tr>
                                @endif
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    @if (!$selectedDominion->isLocked())
                        <a href="{{ route('dominion.forum.create') }}" class="btn btn-primary">New Thread</a>
                    @else
                        <button class="btn btn-primary disabled">New Thread</button>
                    @endif
                    <div class="pull-right">
                        {{ $forumThreads->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here.</p>
                    <p>There {{ ($forumThreads->count() === 1) ? 'is' : 'are' }} {{ number_format($forumThreads->count()) }} {{ str_plural('thread', $forumThreads->count()) }} in the forum.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
