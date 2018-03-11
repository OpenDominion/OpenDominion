<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title>@yield('title', 'OpenDominion')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="apple-mobile-web-app-title" content="OpenDominion">
    <meta name="application-name" content="OpenDominion">
    <meta name="theme-color" content="#ffffff">

    @include('partials.styles')

    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body class="hold-transition skin-blue sidebar-mini">
{!! Analytics::render() !!}

<div class="wrapper">

    @include('partials.main-header')

    @include('partials.main-sidebar')

    <div class="content-wrapper">
        @include('partials.beta-indicator')

        @hasSection('page-header')
            <div class="content-header">
                <h1>
                    @yield('page-header')

                    @hasSection('page-subheader')
                        <small>
                            @yield('page-subheader')
                        </small>
                    @endif

                    @include('partials.tickers')

                </h1>
                {{--<ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
                    <li class="active">Foo</li>
                </ol>--}}
            </div>
        @endif

        <section class="content">

            @include('partials.alerts')

            @include('partials.resources-overview')

            @yield('content')

        </section>

    </div>

    @include('partials.main-footer')

    @include('partials.control-sidebar')

</div>

@include('partials.scripts')

</body>
</html>
