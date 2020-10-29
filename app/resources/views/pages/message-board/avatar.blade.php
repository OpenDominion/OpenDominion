@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="row">

        <div class="col-sm-12">
            <form action="{{ route('message-board.avatar') }}" method="post" class="form-horizontal" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-wooden-sign"></i> Message Board: Change Avatar</h3>
                        <div class="pull-right">
                            <a href="{{ route('message-board') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                        </div>
                    </div>
                    <div class="box-body">
                        <h4>Default</h4>
                        <div class="row" style="font-size: 48px;">
                            @foreach ($defaultAvatars as $avatar)
                                <div class="col-xs-6 col-sm-3 col-md-2 col-xl-1">
                                    <label class="btn-block text-center" for="{{ $avatar }}" style="border: 1px solid #d2d6de; border-radius: 10px; padding-top: 10px;">
                                        <input type="radio" name="avatar" id="{{ $avatar }}" style="height: 42px;" value="{{ $avatar }}" {{ (isset($user->settings['boardavatar']) && $user->settings['boardavatar'] == $avatar) ? 'checked' : null }} />
                                        <i class="ra {{ $avatar }} text-muted"></i>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <h4>Valhalla</h4>
                        <div class="row" style="font-size: 48px;">
                            @foreach ($rankings as $ranking)
                                <div class="col-xs-6 col-sm-3 col-md-2 col-xl-1">
                                    <label class="btn-block text-center" for="{{ $ranking['title_icon'] }}" style="border: 1px solid #d2d6de; border-radius: 10px; padding-top: 10px;">
                                        <input type="radio" name="avatar" id="{{ $ranking['title_icon'] }}" style="height: 42px;" value="{{ $ranking['title_icon'] }}" {{ (isset($user->settings['boardavatar']) && $user->settings['boardavatar'] == $ranking['title_icon']) ? 'checked' : null }} {{ $previousRankings->contains($ranking['key']) ? null : 'disabled' }} />
                                        <i class="ra {{ $ranking['title_icon'] }} text-muted" style="{{ $previousRankings->contains($ranking['key']) ? null : 'opacity: 0.5' }}"></i>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="box-footer">
                        <button class="btn btn-primary" type="submit">Select Avatar</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
@endsection
