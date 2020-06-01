@extends('layouts.master')

@section('page-header', 'Forum')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-comments"></i> Announcement: {{ $announcement->title }}</h3>
                    <div class="pull-right">
                        <a href="{{ route('dominion.forum') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                    </div>
                </div>
                <div class="box-header with-border">
                    <div class="user-block pull-left">
                        <i class="ra ra-crown text-muted pull-left" style="font-size: 36px;"></i>
                        <span class="username">The Emperor</span>
                        <span class="description">
                            posted at {{ $announcement->created_at }}
                        </span>
                    </div>
                </div>
                <div class="box-body">
                    {!! Markdown::convertToHtml($announcement->body) !!}
                </div>
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
