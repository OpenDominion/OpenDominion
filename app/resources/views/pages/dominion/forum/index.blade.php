@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-comments"></i> Forum: {{ $round->name }}</h3>
                </div>
                @if ($protectionService->isUnderProtection($selectedDominion))
                    <div class="box-body">
                        You are currently under protection for <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours and may not access the forum during that time.
                    </div>
                @else
                    <div class="box-body">
                        <table class="table table-hover">
                            <colgroup>
                                {{--<col width="50">--}}
                                <col>
                                <col width="10%">
                                {{--<col width="100">--}}
                                <col width="20%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th{{-- colspan="2"--}}>Topics</th>
                                    <th class="text-center">Replies</th>
                                    {{--<th class="text-center">Views</th>--}}
                                    <th class="text-center">Last Reply</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!$forumThreads->isEmpty())
                                    @foreach ($forumThreads as $thread)
                                        <tr>
                                            {{--<td class="text-center align-middle">
                                                <i class="fa fa-star"></i>
                                            </td>--}}
                                            <td>
                                                <a href="{{ route('dominion.forum.thread', $thread) }}"><b>{{ $thread->title }}</b></a><br>
                                                <small class="text-muted">
                                                    Created {{ $thread->created_at }} by 
                                                    @if ($thread->dominion->isMonarch())
                                                        <i class="ra ra-queen-crown text-red"></i>
                                                    @endif
                                                    <b>{{ $thread->dominion->name }}</b>
                                                    (#{{ $thread->dominion->realm->number }})
                                                </small>
                                            </td>
                                            <td class="text-center align-middle">
                                                {{ number_format($thread->posts->count()) }}
                                            </td>
                                            {{--<td class="text-center align-middle">
                                                0
                                            </td>--}}
                                            <td class="text-center align-middle">
                                                @if (!$thread->posts->isEmpty())
                                                    {{ $thread->posts->last()->created_at }}<br>
                                                    <small class="text-muted">
                                                        by
                                                        @if ($thread->posts->last()->dominion->isMonarch())
                                                            <i class="ra ra-queen-crown text-red"></i>
                                                        @endif
                                                        <b>{{ $thread->posts->last()->dominion->name }}</b>
                                                        (#{{ $thread->posts->last()->dominion->realm->number }})
                                                    </small>
                                                @else
                                                    None
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5">No threads found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div class="box-footer {{--clearfix--}}">
                        @if (!$selectedDominion->isLocked())
                            <a href="{{ route('dominion.forum.create') }}" class="btn btn-primary">New Thread</a>
                        @else
                            <button class="btn btn-primary disabled">New Thread</button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here.</p>
                    <p>There {{ ($forumThreads->count() === 1) ? 'is' : 'are' }} {{ number_format($forumThreads->count()) }} {{ str_plural('thread', $forumThreads->count()) }} {{--and {{ number_format($forumThreads->posts->count()) }} {{ str_plural('post', $forumThreads->posts->count()) }} --}}in the forum.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
