<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>@yield('title', 'OpenDominion')</title>

    @include('partials.styles')

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition skin-blue layout-top-nav">
{!! Analytics::render() !!}

<div class="wrapper">

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="container">

                <!-- Navbar Header -->
                <div class="navbar-header">
                    <a href="{{ url('') }}" class="navbar-brand">Open<b>Dominion</b></a>
                    <button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>

                <!-- Navbar Left Menu -->
                <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="{{ route('home') }}">Error 503 <span class="sr-only">(current)</span></a></li>
                    </ul>
                </div>

            </div>
        </nav>
    </header>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container">

            <div class="content">

                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">

                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">Status 503: Service Unavailable</h3>
                            </div>
                            <div class="box-body">
                                <p>
                                    @if ($exception instanceof \Illuminate\Foundation\Http\Exceptions\MaintenanceModeException)
                                        OpenDominion is currently down for maintenance.
                                    @else
                                        OpenDominion encountered a server error.
                                    @endif
                                </p>
                                <p>
                                    <dl>
                                        <dt>Message:</dt>
                                        <dd>{{ $exception->getMessage() }}</dd>
                                        @if ($exception instanceof \Illuminate\Foundation\Http\Exceptions\MaintenanceModeException)
                                            <dt>Went down:</dt>
                                            <dd>
                                                <abbr title="Went down at {{ $exception->wentDownAt }}">
                                                    {{ $exception->wentDownAt->diffInMinutes(\Carbon\Carbon::now()) + 1 }} minute(s) ago
                                                </abbr>
                                            </dd>
                                            <dt>Estimated back:</dt>
                                            <dd>
                                                @if (\Carbon\Carbon::now() >= $exception->willBeAvailableAt)
                                                    Any second now!
                                                @else
                                                    In {{ $exception->willBeAvailableAt->diffInMinutes(\Carbon\Carbon::now()) + 1 }} minute(s)
                                                @endif
                                            </dd>
                                        @endif
                                    </dl>
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
