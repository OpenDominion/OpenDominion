@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-group"></i> Council: {{ $realm->name }} (#{{ number_format($realm->number) }})</h3>
                </div>
                <div class="box-body">
                    <table class="table table-hover">
                        <colgroup>
                            {{--<col width="50">--}}
                            <col>
                            <col width="10%">
                            {{--<col width="100">--}}
                            <col width="25%">
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
                            @if (!$councilThreads->isEmpty())
                                @foreach ($councilThreads as $thread)
                                    <tr>
                                        {{--<td class="text-center align-middle">
                                            <i class="fa fa-star"></i>
                                        </td>--}}
                                        <td class="align-middle">
                                            <a href="{{ route('dominion.council.thread', $thread) }}" class="{{ $thread->last_activity > $lastRead ? 'text-bold' : null }}">
                                                {{ $thread->title }}
                                            </a>
                                            @php
                                                $pageCount = rceil($thread->posts->count() / $resultsPerPage);
                                            @endphp
                                            @if ($pageCount > 1)
                                                <span class="small" style="margin-left: 10px;">
                                                    @foreach (range(1, $pageCount) as $page)
                                                        <a href="{{ route('dominion.council.thread', $thread) }}?page={{ $page }}"><span class="label label-primary">{{ $page }}</span></a>
                                                    @endforeach
                                                </span>
                                            @endif
                                            <br>
                                            <small class="text-muted">
                                                Created {{ $thread->created_at }} by 
                                                @if ($thread->dominion->isMonarch())
                                                    <i class="ra ra-queen-crown text-red"></i>
                                                @endif
                                                <b>{{ $thread->dominion->name }}</b>
                                                @if ($thread->dominion->name !== $thread->dominion->ruler_name)
                                                    ({{ $thread->dominion->ruler_name }})
                                                @endif
                                            </small>
                                            @if ($selectedDominion->isMonarch() || ($thread->posts->isEmpty() && $selectedDominion->id == $thread->dominion->id))
                                                <a href="{{ route('dominion.council.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            {{ number_format($thread->posts->count()) }}
                                        </td>
                                        {{--<td class="text-center align-middle">
                                            0
                                        </td>--}}
                                        <td class="text-center align-middle">
                                            @if (!$thread->posts->isEmpty())
                                                {{ $thread->latestPost->created_at }}<br>
                                                <small class="text-muted">
                                                    by
                                                    @if ($thread->latestPost->dominion->isMonarch())
                                                        <i class="ra ra-queen-crown text-red"></i>
                                                    @endif
                                                    <b>{{ $thread->latestPost->dominion->name }}</b>
                                                    @if ($thread->latestPost->dominion->name !== $thread->latestPost->dominion->ruler_name)
                                                        ({{ $thread->latestPost->dominion->ruler_name }})
                                                    @endif
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
                <div class="box-footer">
                    @if (!$selectedDominion->isLocked())
                        <a href="{{ route('dominion.council.create') }}" class="btn btn-primary">New Thread</a>
                    @else
                        <button class="btn btn-primary disabled">New Thread</button>
                    @endif
                    <div class="pull-right">
                        {{ $councilThreads->links() }}
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
                    <p>The council is where you can communicate with the rest of your realm. Only you and other dominions inside your realm can view and post here.</p>
                    {{--<p>Your realm monarch is X and has the power to moderate the council board.</p>--}}
                    <p>There {{ ($councilThreads->count() === 1) ? 'is' : 'are' }} {{ number_format($councilThreads->count()) }} {{ str_plural('thread', $councilThreads->count()) }} in the council.</p>
                </div>
            </div>
            @include('partials.dominion.join-discord')
        </div>

    </div>
@endsection
