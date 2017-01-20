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

    {!! Analytics::render() !!}
</head>
<body class="hold-transition skin-blue layout-top-nav">

<div class="wrapper">

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="container">

                <!-- Navbar Header -->
                <div class="navbar-header">
                    <a href="#" class="navbar-brand">Open<b>Dominion</b></a>
                    <button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>

                <!-- Navbar Left Menu -->
                <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="{{ Route::is('home') ? 'active' : null }}"><a href="{{ route('home') }}">Home <span class="sr-only">(current)</span></a></li>
                        {{--<li><a href="#">About</a></li>--}}
                    </ul>
                </div>

                <!-- Navbar Right Menu -->
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">
                        <li class="{{ Route::is('auth.register') ? 'active' : null }}"><a href="{{ route('auth.register') }}">Register</a></li>
                        <li class="{{ Route::is('auth.login') ? 'active' : null }}"><a href="{{ route('auth.login') }}">Login</a></li>
                    </ul>
                </div>

            </div>
        </nav>
    </header>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="container">

            @hasSection('page-header')
                <div class="content-header">
                    <h1>
                        @yield('page-header')

                        @hasSection('page-subheader')
                            <small>
                                @yield('page-subheader')
                            </small>
                        @endif
                    </h1>
                </div>
            @endif

            <div class="content">

                @include('partials.alerts')

                @yield('content')

            </div>

        </div>
    </div>

    @include('partials.main-footer')

</div>

@include('partials.scripts')

</body>
</html>
