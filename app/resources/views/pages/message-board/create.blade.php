@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="ra ra-wooden-sign"></i> Message Board: Create Thread</h3>
                    <div class="float-end">
                        <a href="{{ route('message-board') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                <form action="{{ route('message-board.create') }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    <div class="card-body">

                        {{-- Title --}}
                        <div class="form-group">
                            <label for="category" class="col-sm-3 control-label">Category</label>
                            <div class="col-sm-9">
                                <select name="category" id="category" class="form-control">
                                    @foreach ($categories as $category)
                                        @if ($category->role_required == null || $user->hasRole($category->role_required))
                                            <option value="{{ $category->id }}" {{ $category->id == $selectedCategory ? 'selected' : null }}>
                                                {{ $category->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Title --}}
                        <div class="form-group">
                            <label for="title" class="col-sm-3 control-label">Title</label>
                            <div class="col-sm-9">
                                <input type="text" name="title" id="title" class="form-control" placeholder="Title" value="{{ old('title') }}" required autofocus>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="form-group">
                            <label for="body" class="col-sm-3 control-label">Body</label>
                            <div class="col-sm-9">
                                <textarea name="body" id="body" cols="30" rows="10" class="form-control" placeholder="Body" required>{{ old('body') }}</textarea>
                                <p class="form-text">
                                    Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.
                                </p>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create Thread</button>
                        <p class="form-text float-end">
                            You are posting as <b>{{ $user->display_name }}</b>.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Information</h3>
                </div>
                <div class="card-body">
                    <p>The message board is where you can communicate with other players. All registered users can view and post here.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
