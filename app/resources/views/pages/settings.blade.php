@extends('layouts.master')

@section('page-header', 'Settings')

@section('content')
    @php
        $user = Auth::user();
    @endphp

    <form action="{{ route('settings') }}" method="post" enctype="multipart/form-data" role="form">
        @csrf

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item"><a href="#account" class="nav-link active" data-bs-toggle="tab">Account</a></li>
                    <li class="nav-item"><a href="#notifications" class="nav-link" data-bs-toggle="tab">Notifications</a></li>
                    {{--<li class="nav-item"><a href="#security" class="nav-link" data-bs-toggle="tab">Security</a></li>--}}
                </ul>
            </div>
            <div class="card-body">
            <div class="tab-content">

                <div class="tab-pane active" id="account">
                    <div class="row">
                        <div class="col-md-6">

                            <h2 class="border-bottom pb-2 mb-3">Private Information</h2>

                            {{-- Email --}}
                            <div class="mb-3 row">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" name="account_email" id="email" class="form-control" value="{{ $user->email }}" readonly>
                                    <p class="form-text mb-0">
                                        This can only be changed by an admin -
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#reportModal" data-report-type="account">Request a change</a>.
                                    </p>
                                </div>
                            </div>

                            {{-- Discord --}}
                            @if ($discordHelper->getClientId())
                                <div class="mb-3 row">
                                    @if ($discordUser = $user->discordUser()->first())
                                        <label class="col-sm-3 col-form-label">Discord Account</label>
                                        <div class="col-sm-9">
                                            <p class="form-control-plaintext">{{ $discordUser->username }}#{{ $discordUser->discriminator }}</p>
                                            <a href="{{ route('discord-unlink') }}" class="btn btn-danger">
                                                <i class="fa fa-unlink"></i> Unlink account
                                            </a>
                                        </div>
                                    @else
                                        <label class="col-sm-3 col-form-label">Discord</label>
                                        <div class="col-sm-9">
                                            <a href="{{ $discordHelper->getDiscordConnectUrl('link') }}" class="btn btn-primary">
                                                <i class="fa fa-link"></i> Link account
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Advisors --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Shared Advisors</label>
                                <div class="col-sm-9">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="packadvisors" id="packadvisors" {{ $user->getSetting('packadvisors') === false ? null : 'checked' }} />
                                        <label class="form-check-label" for="packadvisors">Allow <b>packmates</b> to view your advisors.</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="realmadvisors" id="realmadvisors" {{ $user->getSetting('realmadvisors') === false ? null : 'checked' }} />
                                        <label class="form-check-label" for="realmadvisors">Allow <b>realmmates</b> to view your advisors.</label>
                                    </div>
                                    <p class="form-text">Shared advisors can still be enabled/disabled per dominion on the government page, these settings only determine the default values (does not apply to late starters).</p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="shareusername" id="shareusername" {{ $user->getSetting('shareusername') === false ? null : 'checked' }} />
                                        <label class="form-check-label" for="shareusername">Share display name alongside advisors.</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">

                            <h2 class="border-bottom pb-2 mb-3">Public Information</h2>

                            {{-- Display Name --}}
                            <div class="mb-3 row">
                                <label for="display_name" class="col-sm-3 col-form-label">Display Name</label>
                                <div class="col-sm-9">
                                    <input type="text" id="display_name" class="form-control" value="{{ $user->display_name }}" readonly>
                                    <p class="form-text mb-0">
                                        This can only be changed by an admin -
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#reportModal" data-report-type="account">Request a change</a>.
                                    </p>
                                </div>
                            </div>

                            {{-- Country --}}
                            <div class="mb-3 row">
                                <label for="country" class="col-sm-3 col-form-label">Country</label>
                                <div class="col-sm-9">
                                    @php $selectedCountry = $user->getSetting('country'); @endphp
                                    <select name="country" id="country" class="form-control select2-country" style="width: 100%;" data-placeholder="Select a country (optional)">
                                        <option value=""></option>
                                        @foreach ($countryHelper->getCountries() as $code => $name)
                                            <option value="{{ $code }}" data-code="{{ $code }}" {{ $selectedCountry === $code ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="form-text">Displays a flag on your <a href="{{ route('valhalla.user', $user->id) }}">public profile</a>.</p>
                                </div>
                            </div>

                            {{-- Avatar (RPG icon shown on profile and message board) --}}
                            <div class="mb-3 row">
                                <label class="col-sm-3 col-form-label">Avatar</label>
                                <div class="col-sm-9">
                                    <div style="margin-bottom: 10px;">
                                        <i class="ra {{ $user->getSetting('boardavatar') ?: 'ra-player' }} text-muted" style="font-size: 64px;"></i>
                                    </div>
                                    <a href="{{ route('message-board.avatar') }}" class="btn btn-secondary">
                                        <i class="fa fa-pencil"></i> Change avatar
                                    </a>
                                    <p class="form-text">The RPG icon shown on your <a href="{{ route('valhalla.user', $user->id) }}">public profile</a> and message board posts.</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="tab-pane" id="notifications">
                    <div class="row">
                        <div class="col-sm-6">

                            <h2 class="border-bottom pb-2 mb-3">Notifications</h2>

                            @foreach ($notificationHelper->getNotificationCategories() as $category => $notifications)
                                <table class="table table-striped table-hover">
                                    <colgroup>
                                        <col>
                                        <col width="15%">
                                        <col width="15%">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>{{ $notificationHelper->getNotificationTypeLabel($category) }}</th>
                                            <th class="text-center">Email</th>
                                            <th class="text-center">Ingame</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><em>All {{ $notificationHelper->getNotificationTypeLabel($category) }}</em></td>
                                            <td class="text-center">
                                                <input type="checkbox" data-check-all data-check-all-type="email" {{ collect($notificationSettings[$category] ?? [])->map(function ($notification) { return $notification['email'] ?? false; })->reduce(function ($carry, $item) { return (($carry || ($carry === null)) && $item); }) ? 'checked' : null }}>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" data-check-all data-check-all-type="ingame" {{ collect($notificationSettings[$category] ?? [])->map(function ($notification) { return $notification['ingame'] ?? false; })->reduce(function ($carry, $item) { return (($carry || ($carry === null)) && $item); }) ? 'checked' : null }}>
                                            </td>
                                        </tr>
                                        @foreach ($notifications as $type => $notification)
                                            <tr>
                                                <td>{{ $notification['label'] }}</td>
                                                <td class="text-center">
                                                    <input type="checkbox" name="notifications[{{ $category }}][{{ $type }}][email]" {{ array_get($notificationSettings, "{$category}.{$type}.email", $notification['defaults']['email']) ? 'checked' : null }} data-check-all-type="email">
                                                </td>
                                                <td class="text-center">
                                                    @if ($notification['onlyemail'] ?? false)
                                                        &nbsp;
                                                    @else
                                                        <input type="checkbox" name="notifications[{{ $category }}][{{ $type }}][ingame]" {{ array_get($notificationSettings, "{$category}.{$type}.ingame", $notification['defaults']['ingame']) ? 'checked' : null }} data-check-all-type="ingame">
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endforeach

                        </div>
                        {{--<div class="col-sm-6">

                            <h2 class="border-bottom pb-2 mb-3">Notification Settings</h2>--}}

                            {{-- Disable email notifications --}}
                            {{--<div class="checkbox">
                                <label>
                                    <input type="checkbox">
                                    Disable email notifications
                                </label>
                                <p class="help-text">foo</p>
                            </div>--}}

                            {{-- Digest Email --}}
                            {{--<div class="mb-3">
                                <label>Digest Irregular Email Notifications</label>
                                <br>
                                <div class="btn-group" data-bs-toggle="buttons">
                                    @foreach ([
                                        'off' => 'Off',
                                        '5min' => '5 Min',
                                        'hourly' => 'Hourly',
                                        'daily' => 'Daily',
                                    ] as $notificationKey => $label)
                                        @php
                                        if ($user->getSetting('notification_digest') === null) {
                                            $isActive = ($notificationKey === 'hourly');
                                        } else {
                                            $isActive = ($user->getSetting('notification_digest')  === $notificationKey);
                                        }
                                        @endphp
                                        <label class="btn btn-secondary {{ $isActive ? 'active' : null }}">
                                            <input type="radio" name="notification_digest" value="{{ $notificationKey }}" autocomplete="off" {{ $isActive ? 'checked' : null }}>
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                <p class="help-text">Having a low digest setting can result in a lot of emails.</p>
                            </div>

                        </div>--}}

                    </div>
                </div>

                {{--<div class="tab-pane" id="security">
                    security
                </div>--}}

            </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Settings</button>
            </div>
        </div>
    </form>

@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {

            // Country dropdown with flag icons
            var formatCountry = function (state) {
                if (!state.id) return state.text;
                var code = $(state.element).data('code');
                return $('<span><span class="fi fi-' + code + '" style="margin-right: 8px;"></span>' + state.text + '</span>');
            };
            $('.select2-country').select2({
                templateResult: formatCountry,
                templateSelection: formatCountry,
                allowClear: true,
                placeholder: $('.select2-country').data('placeholder'),
            });

            // Display filename and filesize on avatar upload
            $(document).on('change', ':file', function () {
                var input = $(this);
                var file = input.get(0).files[0];
                var fileName = input.val().replace(/\\/g, '/').replace(/.*\//, '');

                $('.new-avatar-filename').text(fileName + ' (' + formatBytes(file.size) + ')');
            });

            $('#notifications input[type=checkbox]').change(function (e) {
                var input = $(this);
                var inputType = input.data('check-all-type') ? input.data('check-all-type') : 'email'
                var inputIsAllCheckbox = input.is('[data-check-all]');

                var allCheckbox = input.parents('tbody').find('input[type="checkbox"][data-check-all][data-check-all-type="' + inputType + '"]');
                var allCheckboxes = input.parents('tbody').find('input[type="checkbox"][data-check-all-type="' + inputType + '"]').not('[data-check-all]');

                if (inputIsAllCheckbox) {
                    allCheckboxes.each(function () {
                        $(this).prop('checked', allCheckbox.prop('checked'));
                    });
                } else {
                    allCheckbox.prop('checked', (allCheckboxes.filter(':checked').length === allCheckboxes.length) ? 'checked' : '');
                }
            });

            // Javascript to enable link to tab
            var hash = document.location.hash;
            var prefix = "tab_";
            if (hash) {
                var tabEl = document.querySelector('.nav-tabs a[href="' + hash.replace(prefix, '') + '"]');
                if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
            }

            // Change hash for page-reload
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                window.location.hash = e.target.hash.replace("#", "#" + prefix);
            });

        })(jQuery);
    </script>
@endpush
