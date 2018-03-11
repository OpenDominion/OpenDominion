@auth
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
                            @php
                            switch ($notification->type) {
                                case \OpenDominion\Notifications\Dominion\LandExploredNotification::class:
                                    $iconClass = 'fa fa-search';
                                    $route = route('dominion.explore');
                                    break;
                                default:
                                    $iconClass = null;
                                    $route = null;
                            }
                            @endphp
                            <li>
                                <a href="{{ $route }}">
                                    <i class="{{ $iconClass }}"></i>
                                    {{ $notification->data['message'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>
                <li class="footer">
                    <a href="#" id="clear-notifications">Clear Notifications</a>
                    <form action="{{ url('tmp/clear-notifications') }}" method="post" id="clear-notifications-form"></form>
                </li>
            @endif
        </ul>
    </li>
@endauth

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
