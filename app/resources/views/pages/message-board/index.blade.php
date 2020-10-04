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
                        @foreach ($categories as $category)
                            <thead>
                                @if (!$loop->first)
                                    <tr><td colspan="3"><!-- Separator --></td></tr>
                                @endif
                                <tr>
                                    <th>{{ $category->name }}<a href="{{ route('message-board.category', $category->slug) }}" class="small" style="margin-left: 10px">view all</a></th>
                                    <th class="text-center">Replies</th>
                                    <th class="text-center">Posted At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (!$category->threads->isEmpty())
                                    @foreach ($category->threads as $thread)
                                        <tr>
                                            <td class="align-middle">
                                                <a href="{{ route('message-board.thread', $thread) }}"><b>{{ $thread->title }}</b></a><br>
                                                <small class="text-muted">
                                                    Created {{ $thread->created_at }} by
                                                    <b>{{ $thread->user->display_name }}</b> {!! $thread->user->displayRoleHtml() !!}
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
                                                        <b>{{ $thread->latestPost->user->display_name }}</b> {!! $thread->latestPost->user->displayRoleHtml() !!}
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
                        @endforeach
                    </table>
                </div>
                <div class="box-footer">
                    <a href="{{ route('message-board.create') }}" class="btn btn-primary">New Thread</a>
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
                    <p>You may also <a href="{{ route('message-board.avatar') }}">change your avatar</a>.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
