@extends('layouts.master')

@section('page-header', 'Settings')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-cog"></i> Settings</h3>
                </div>
                <form class="form" action="{{ route('dominion.misc.settings') }}" method="post">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label class="form-label">Preferred Title:</label>
                            <select name="title" class="form-control" name="preferred_title">
                                @foreach ($rankingsHelper->getRankings() as $ranking)
                                    <option value="{{ $ranking['key'] }}" {{ isset($selectedDominion->settings['preferred_title']) && $selectedDominion->settings['preferred_title'] == $ranking['key'] ? 'selected' : null }}>
                                        {{ $ranking['name'] }} - "{{ $ranking['title'] }}"
                                    </option>
                                @endforeach
                            </select>
                            <span class="small">Used in round forum if you currently hold this title, otherwise uses the first in the list.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Title Icon:</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="show_icon" {{ isset($selectedDominion->settings['show_icon']) && $selectedDominion->settings['show_icon'] == 'on' ? 'checked' : null }} />
                                    Display icon on the realm page
                                </label>
                            </div>
                            <span class="small">Uses your preferred title, if possible.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Shadow League:</label>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="black_guard_icon" value="private" checked />
                                    Visible to members only
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="black_guard_icon" value="public" {{ isset($selectedDominion->settings['black_guard_icon']) && $selectedDominion->settings['black_guard_icon'] == 'public' ? 'checked' : null }} />
                                    Visible to everyone
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary">Update Settings</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
