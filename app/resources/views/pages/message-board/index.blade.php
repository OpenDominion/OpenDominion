@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-wooden-sign"></i> Message Board</h3>
                </div>
                <div class="box-body">
                    <table class="table table-hover">
                        <colgroup>
                            <col>
                            <col width="10%">
                            <col width="25%">
                        </colgroup>
                        {{--
                        <thead>
                            <tr>
                                <th>Announcements</th>
                                <th class="text-center">Replies</th>
                                <th class="text-center">Posted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!$announcements->isEmpty())
                                @foreach ($announcements as $announcement)
                                    <tr>
                                        <td>
                                            <a href="{{ route('dominion.forum.announcement', $announcement) }}">
                                                <b>{{ $announcement->title }}</b>
                                            </a>
                                        </td>
                                        <td class="text-center align-middle">--</td>
                                        <td class="text-center align-middle">
                                            {{ $announcement->created_at }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="text-center" colspan="3">No announcements found</td>
                                </tr>
                            @endif
                        </tbody>
                        --}}
                        <thead>
                            <tr><td colspan="3"><!-- Separator --></td></tr>
                            <tr>
                                <th>Topics</th>
                                <th class="text-center">Replies</th>
                                <th class="text-center">Last Reply</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if (!$threads->isEmpty())
                                @foreach ($threads as $thread)
                                    <tr>
                                        <td class="align-middle">
                                            <a href="{{ route('message-board.thread', $thread) }}"><b>{{ $thread->title }}</b></a><br>
                                            <small class="text-muted">
                                                Created {{ $thread->created_at }} by
                                                <b>{{ $thread->user->display_name }}</b>
                                            </small>
                                        </td>
                                        <td class="text-center align-middle">
                                            {{ number_format($thread->posts->count()) }}
                                        </td>
                                        <td class="text-center align-middle">
                                            @if (!$thread->posts->isEmpty())
                                                {{ $thread->posts->last()->created_at }}<br>
                                                <small class="text-muted">
                                                    by
                                                    <b>{{ $thread->posts->last()->user->display_name }}</b>
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
                        </tbody>
                    </table>
                </div>
                <div class="box-footer">
                    <a href="{{ route('message-board.create') }}" class="btn btn-primary">New Thread</a>
                    <div class="pull-right">
                        {{ $threads->links() }}
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
                    <p>The message board is where you can communicate with other players. All registered users can view and post here.</p>
                    <p>There {{ ($threads->count() === 1) ? 'is' : 'are' }} {{ number_format($threads->count()) }} {{ str_plural('thread', $threads->count()) }} in the message board.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
