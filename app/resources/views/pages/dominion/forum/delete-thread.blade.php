@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Delete Thread</h3>
        </div>
        <form action="{{ route('dominion.forum.delete.thread', $thread) }}" method="post" class="form-horizontal" role="form">
            @csrf
            <div class="card-body">
                @if ($thread->posts->isEmpty())
                    Are you sure you want to delete this thread and all of its contents?
                @else
                    Are you sure you want to delete the content of this initial post?
                @endif
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Delete Thread</button>
            </div>
        </form>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Thread: {{ $thread->title }}</h3>
        </div>
        <div class="card-body">
            {!! Markdown::convertToHtml($thread->body) !!}
        </div>
        <div class="card-footer">
            <small>
                <i>
                    Posted {{ $thread->created_at }} by
                    <b>{{ $thread->dominion->name }}</b> (#{{ $thread->dominion->realm->number }})
                </i>
            </small>
        </div>
    </div>
@endsection
