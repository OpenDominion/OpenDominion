@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-comments"></i> Forum: Create Thread</h3>
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
            </div>
        </form>
    </div>
@endsection
