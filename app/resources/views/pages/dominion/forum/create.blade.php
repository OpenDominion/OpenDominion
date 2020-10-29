@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-comments"></i> Round Forum: Create Thread</h3>
                    <div class="pull-right">
                        <a href="{{ route('dominion.forum') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                <form action="{{ route('dominion.forum.create') }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    <div class="box-body">

                        {{-- Title --}}
                        <div class="form-group">
                            <label for="title" class="col-sm-3 control-label">Title</label>
                            <div class="col-sm-9">
                                <input type="text" name="title" id="title" class="form-control" placeholder="Title" value="{{ old('title') }}" required autofocus {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            </div>
                        </div>

                        {{-- Body --}}
                        <div class="form-group">
                            <label for="body" class="col-sm-3 control-label">Body</label>
                            <div class="col-sm-9">
                                <textarea name="body" id="body" cols="30" rows="10" class="form-control" placeholder="Body" required {{ $selectedDominion->isLocked() ? 'disabled' : null }}>{{ old('body') }}</textarea>
                                <p class="help-block">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                            </div>
                        </div>

                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Create Thread</button>
                        <p class="help-block pull-right">
                            You are posting as <b>{{ $selectedDominion->name }} (#{{ $selectedDominion->realm->number }})</b>.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
        <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The forum is where you can communicate with the rest of the world. All dominions can view and post here.</p>
                    @include('partials.forum-rules')
                </div>
            </div>
        </div>

    </div>
@endsection
