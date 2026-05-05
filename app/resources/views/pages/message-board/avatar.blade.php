@extends('layouts.master')

@section('page-header', 'Message Board')

@section('content')
    <div class="row">

        <div class="col-sm-12">
            <form action="{{ route('message-board.avatar') }}" method="post" role="form">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <span class="card-title"><i class="ra ra-wooden-sign"></i> Message Board: Change Avatar</span>
                        <div class="float-end">
                            <a href="{{ route('message-board') }}"><i class="fa fa-chevron-left"></i><i class="fa fa-chevron-left"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <h4>Default</h4>
                        <div class="row gy-2" style="font-size: 48px;">
                            @foreach ($defaultAvatars as $avatar)
                                <div class="col-6 col-sm-3 col-md-2">
                                    <label class="avatar-option" for="{{ $avatar }}">
                                        <input type="radio" name="avatar" id="{{ $avatar }}" class="form-check-input m-0" value="{{ $avatar }}" {{ (isset($user->settings['boardavatar']) && $user->settings['boardavatar'] == $avatar) ? 'checked' : null }} />
                                        <i class="ra {{ $avatar }} text-muted"></i>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <h4 class="mt-3">Valhalla</h4>
                        <div class="row gy-2" style="font-size: 48px;">
                            @foreach ($rankings as $ranking)
                                <div class="col-6 col-sm-3 col-md-2">
                                    <label class="avatar-option" for="{{ $ranking['title_icon'] }}" data-bs-toggle="tooltip" title="{{ $ranking['name'] }}">
                                        <input type="radio" name="avatar" id="{{ $ranking['title_icon'] }}" class="form-check-input m-0" value="{{ $ranking['title_icon'] }}" {{ (isset($user->settings['boardavatar']) && $user->settings['boardavatar'] == $ranking['title_icon']) ? 'checked' : null }} {{ $previousRankings->contains($ranking['key']) ? null : 'disabled' }} />
                                        <i class="ra {{ $ranking['title_icon'] }} text-muted" style="{{ $previousRankings->contains($ranking['key']) ? null : 'opacity: 0.5' }}"></i>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @if (!$achievements->isEmpty())
                        <h4 class="mt-3">Achievements</h4>
                        <div class="row gy-2" style="font-size: 48px;">
                            @foreach ($achievements as $achievement)
                                <div class="col-6 col-sm-3 col-md-2">
                                    <label class="avatar-option" for="{{ $achievement['icon'] }}" data-bs-toggle="tooltip" title="{{ $achievement['name'] }}<br/>{{ $achievement['description'] }}">
                                        <input type="radio" name="avatar" id="{{ $achievement['icon'] }}" class="form-check-input m-0" value="{{ $achievement['icon'] }}" {{ (isset($user->settings['boardavatar']) && $user->settings['boardavatar'] == $achievement['icon']) ? 'checked' : null }} {{ $userAchievements->contains($achievement['id']) ? null : 'disabled' }} />
                                        <i class="ra {{ $achievement['icon'] }} text-muted" style="{{ $userAchievements->contains($achievement['id']) ? null : 'opacity: 0.5' }}"></i>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" type="submit">Select Avatar</button>
                    </div>
                </div>
            </form>
        </div>

    </div>
@endsection
