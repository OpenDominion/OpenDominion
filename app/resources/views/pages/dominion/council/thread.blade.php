@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-group"></i> Council Thread: {{ $thread->title }}</span>
                    <div class="float-end">
                        <a href="{{ route('dominion.council') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                @if ($posts->currentPage() == 1)
                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                        <div class="d-flex align-items-center gap-3 min-w-0">
                            <i class="ra {{ $thread->dominion->isMonarch() ? 'ra-queen-crown text-red' : 'ra-knight-helmet text-muted' }} flex-shrink-0" style="font-size: 36px; line-height: 1;"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold">
                                    {{ $thread->dominion->name }}
                                    @if ($thread->dominion->name !== $thread->dominion->ruler_name)
                                        <span class="text-body-secondary fw-normal">({{ $thread->dominion->ruler_name }})</span>
                                    @endif
                                </div>
                                <div class="small text-body-secondary">
                                    posted at {{ $thread->created_at }}
                                </div>
                            </div>
                        </div>
                        @if ($selectedDominion->isMonarch() || ($thread->posts->isEmpty() && $selectedDominion->id == $thread->dominion->id))
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <a href="{{ route('dominion.council.delete.thread', $thread) }}"><i class="fa fa-trash text-red"></i></a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        {!! Str::markdown($thread->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                    </div>
                @else
                    <div class="card-body">
                        <em>Initial post and {{ $posts->perPage() * ($posts->currentPage() - 1) }} replies not shown.</em>
                    </div>
                @endif
                @if (!$posts->isEmpty())
                    <div class="card-footer p-0">
                        @foreach ($posts as $post)
                            <div class="p-3 @if (!$loop->last) border-bottom @endif">
                                <div class="d-flex gap-3 text-break">
                                    <i class="ra {{ $post->dominion->isMonarch() ? 'ra-queen-crown text-red' : 'ra-knight-helmet text-muted' }} flex-shrink-0" style="font-size: 26px; line-height: 1;"></i>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-baseline gap-2 mb-2">
                                            <div class="fw-semibold">
                                                {{ $post->dominion->name }}
                                                @if ($post->dominion->name !== $post->dominion->ruler_name)
                                                    <span class="text-body-secondary fw-normal">({{ $post->dominion->ruler_name }})</span>
                                                @endif
                                            </div>
                                            <div class="small text-body-secondary d-flex align-items-center gap-2 flex-shrink-0">
                                                <span>{{ $post->created_at }}</span>
                                                @if ($selectedDominion->isMonarch() || $selectedDominion->id == $post->dominion->id)
                                                    <a href="{{ route('dominion.council.delete.post', $post) }}"><i class="fa fa-trash text-red"></i></a>
                                                @endif
                                            </div>
                                        </div>
                                        {!! Str::markdown($post->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
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
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">Post Reply</span>
                </div>
                <form action="{{ route('dominion.council.reply', $thread) }}" method="post" role="form">
                    @csrf
                    <div class="card-body">

                        {{-- Body --}}
                        <div class="row mb-3">
                            <label for="body" class="col-sm-3 col-form-label">Body</label>
                            <div class="col-sm-9">
                                <textarea name="body" id="body" rows="3" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
                                <p class="form-text">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Post Reply</button>
                        <p class="form-text float-end">
                            You are posting as <b>{{ $selectedDominion->name }} ({{ $selectedDominion->user->display_name }})</b>.
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
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here.</p>
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                    @include('partials.forum-rules')
                </div>
                <div class="card-body">
                    <p>The council is where you can communicate with the rest of your realm. Only you and other dominions inside your realm can view and post here.</p>
                    {{--<p>Your realm monarch is X and has the power to moderate the council board.</p>--}}
                    <p>There {{ ($thread->posts->count() === 1) ? 'is' : 'are' }} {{ number_format($thread->posts->count()) }} {{ str_plural('reply', $thread->posts->count()) }} in this thread.</p>
                </div>
            </div>
            @include('partials.dominion.join-discord')
        </div>

    </div>
@endsection
