@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-wooden-sign"></i> Message Board: Edit Thread</span>
                    <div class="float-end">
                        <a href="{{ route('message-board.thread', $thread) }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                <form action="{{ route('message-board.thread.edit', $thread) }}" method="post" role="form">
                    @csrf
                    <div class="card-body">
                        @include('pages.message-board._form', ['thread' => $thread, 'user' => $user])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('message-board.thread', $thread) }}" class="btn btn-default">Cancel</a>
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
                    <p>Edit this thread's title, body, and (for Announcements) its homepage settings.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
