@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-group"></i> {{ $realm->name }} (#{{ number_format($realm->number) }})</h3>
                </div>
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
                                <th class="text-center">Last Post</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!$councilThreads->isEmpty())
                                @foreach ($councilThreads as $thread)
                                    <tr>
                                        {{--<td class="text-center align-middle">
                                            <i class="fa fa-star"></i>
                                        </td>--}}
                                        <td>
                                            <a href="{{ route('dominion.council.thread', $thread) }}"><b>{{ $thread->title }}</b></a><br>
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
                                            @if ($selectedDominion->isMonarch()) 
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
                                                {{ $thread->posts->last()->created_at }}<br>
                                                <small class="text-muted">
                                                    by
                                                    @if ($thread->posts->last()->dominion->isMonarch())
                                                        <i class="ra ra-queen-crown text-red"></i>
                                                    @endif
                                                    <b>{{ $thread->posts->last()->dominion->name }}</b>
                                                    @if ($thread->posts->last()->dominion->name !== $thread->posts->last()->dominion->ruler_name)
                                                        ({{ $thread->posts->last()->dominion->ruler_name }})
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
                <div class="box-footer {{--clearfix--}}">
                    @if (!$selectedDominion->isLocked())
                        <a href="{{ route('dominion.council.create') }}" class="btn btn-primary">New Thread</a>
                    @else
                        <button class="btn btn-primary disabled">New Thread</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The council is where you can communicate with the rest of your realm. Only you and other dominions inside your realm can view and post in here.</p>
                    {{--<p>Your realm monarch is X and has the power to moderate the council board.</p>--}}
                    <p>There {{ ($councilThreads->count() === 1) ? 'is' : 'are' }} {{ number_format($councilThreads->count()) }} {{ str_plural('thread', $councilThreads->count()) }} {{--and {{ number_format($councilThreads->posts->count()) }} {{ str_plural('post', $councilThreads->posts->count()) }} --}}in the council.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
