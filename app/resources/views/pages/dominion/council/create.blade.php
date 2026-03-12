@extends('layouts.master')

@section('page-header', 'Council')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-group"></i> Council: Create Thread</h3>
                    <div class="float-end">
                        <a href="{{ route('dominion.council') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                <form action="{{ route('dominion.council.create') }}" method="post" class="form-horizontal" role="form">
                    @csrf
                    <div class="card-body">

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
                                <p class="form-text">Markdown is supported with <a href="http://commonmark.org/help/" target="_blank">CommonMark syntax <i class="fa fa-external-link"></i></a>.</p>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Create Thread</button>
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
                    <h3 class="card-title">Information</h3>
                </div>
                <div class="card-body">
                    <p>The council is where you can communicate with the rest of your realm. Only you and other dominions inside your realm can view and post here.</p>
                    {{--<p>Your realm monarch is X and has the power to moderate the council board.</p>--}}
                </div>
            </div>
        </div>

    </div>
@endsection
