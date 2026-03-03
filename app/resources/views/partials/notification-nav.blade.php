@if (auth()->check() && isset($selectedDominion))
    <li class="nav-item dropdown notifications-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fa fa-bell-o"></i>
            @if ($selectedDominion->unreadNotifications->count() > 0)
                <span class="badge text-bg-warning">
                    {{ $selectedDominion->unreadNotifications->count() }}
                </span>
            @endif
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
            @if ($selectedDominion->unreadNotifications->count() === 0)
                <li class="dropdown-header">You have no new notifications.</li>
            @else
                <li class="dropdown-header">You have {{ $selectedDominion->unreadNotifications->count() }} new notifications</li>
                <li>
                    <ul class="menu list-unstyled">
                        @foreach ($selectedDominion->unreadNotifications as $notification)
                            @php
                                $route = array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.route", '#');

                                if (is_callable($route)) {
                                    if (isset($notification->data['data']['_routeParams'])) {
                                        $route = $route($notification->data['data']['_routeParams']);
                                    } else {
                                        // fallback
                                        $route = '#';
                                    }
                                }
                            @endphp
                            <li>
                                <a href="{{ $route }}" class="dropdown-item">
                                    <i class="{{ array_get($notificationHelper->getNotificationCategories(), "{$notification->data['category']}.{$notification->data['type']}.iconClass", 'fa fa-question') }}"></i>
                                    {{ $notification->data['message'] }}<br>
                                    <small class="text-muted">{{ $notification->created_at }}</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
                <li class="dropdown-footer">
                    <a href="#" id="clear-notifications" class="dropdown-item text-center">Clear Notifications</a>
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

                // todo: refactor to api route with laravel passport
                $('#clear-notifications').click(e => {
                    $('#clear-notifications-form').submit();
                });

            })(jQuery);
        </script>
    @endpush
@endif
