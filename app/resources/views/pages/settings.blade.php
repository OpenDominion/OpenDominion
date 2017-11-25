@extends('layouts.master')

@section('page-header', 'Settings')

@section('content')
    @php
        $user = Auth::user();
    @endphp

    <form action="{{ route('settings') }}" method="post" class="form-horizontal" enctype="multipart/form-data" role="form">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#account" data-toggle="tab">Account</a></li>
                <li><a href="#notifications" data-toggle="tab">Notifications</a></li>
                <li><a href="#security" data-toggle="tab">Security</a></li>
            </ul>
            <div class="tab-content">

                <div class="tab-pane active" id="account">
                    <div class="row">
                        <div class="col-md-6">

                            <h2 class="page-header">Basic Information</h2>

                            {{-- Display Name --}}
                            <div class="form-group">
                                <label class="col-sm-2 control-label">Display Name</label>
                                <div class="col-sm-10">
                                    <p class="form-control-static">{{ $user->display_name }}</p>
                                    <p class="help-block">Visible on your <a href="#">public profile</a> and to other players in your realm.</p>
                                    <p class="help-block">Your display name cannot be changed.</p>
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="form-group">
                                <label for="email" class="col-sm-2 control-label">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                                    <p class="help-block">Your email address cannot be changed at the moment.</p>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6">

                            <h2 class="page-header">Avatar</h2>

                            {{-- Avatar --}}
                            <div class="form-group">
                                <div class="col-xs-12">
                                    <div style="margin-bottom: 10px;">
                                        <img src="{{ $user->getAvatarUrl() }}" class="img-responsive" alt="Avatar of {{ $user->display_name }}">
                                    </div>
                                    @if ($user->avatar === null)
                                        <p class="help-block">Your are currently using your <a href="https://en.gravatar.com/" target="_blank">Gravatar <i class="fa fa-external-link"></i></a>.</p>
                                    {{--@else--}}
                                        {{--<p class="help-block">You are using a custom uploaded avatar. <a href="#">Reset to Gravatar</a>.</p>--}}
                                    @endif

                                    <label class="btn btn-default btn-file">
                                        Upload new avatar <input type="file" name="avatar">
                                    </label>

                                    <span class="new-avatar-filename" style="padding-left: 8px;"></span>

                                    <p class="help-block">Uploaded avatars will be cropped/resized to 200x200 pixels and converted to png. Upload a square image for best results.</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="notifications">
                    notifications
                </div>

                <div class="tab-pane" id="security">
                    security
                </div>

            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Settings</button>
    </form>

@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $(document).on('change', ':file', function () {
                var input = $(this);
                var file = input.get(0).files[0];
                var fileName = input.val().replace(/\\/g, '/').replace(/.*\//, '');

                $('.new-avatar-filename').text(fileName + ' (' + formatBytes(file.size) + ')');
            });
        })(jQuery);
    </script>
@endpush
