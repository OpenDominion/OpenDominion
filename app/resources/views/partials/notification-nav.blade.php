@if (auth()->check() && isset($selectedDominion))
    <li class="dropdown notifications-menu">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="fa fa-bell-o"></i>
            @if ($selectedDominion->unreadNotifications->count() > 0)
                <span class="label label-warning">
                    {{ $selectedDominion->unreadNotifications->count() }}
                </span>
            @endif
        </a>
        <ul class="dropdown-menu">
            @if ($selectedDominion->unreadNotifications->count() === 0)
                <li class="header">You have no new notifications.</li>
            @else
                <li class="header">You have {{ $selectedDominion->unreadNotifications->count() }} new notifications</li>
                <li>
                    <ul class="menu">
                        @foreach ($selectedDominion->unreadNotifications as $notification)
                            <li>
                                <a href="{{ array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.route", '#') }}">
                                    <i class="{{ array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.iconClass", 'fa fa-question') }}"></i>
                                    {{ $notification->data['message'] }}<br>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
                <li class="footer">
                    <a href="#" id="clear-notifications">Clear Notifications</a>
                    <form action="{{ route('dominion.misc.clear-notifications') }}" method="post" id="clear-notifications-form">
                        @csrf
                    </form>
                </li>
            @endif
        </ul>
    </li>

    @push('inline-scripts')
        <script type="text/javascript">
            (function ($) {

                // todo: refactor to api route with laravel passwport
                $('#clear-notifications').click(e => {
                    $('#clear-notifications-form').submit();
                });

            })(jQuery);
        </script>
    @endpush
@endif
