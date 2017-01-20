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
<body class="hold-transition skin-blue sidebar-mini">

<div class="wrapper">

    @include('partials.main-header')

    @include('partials.main-sidebar')

    <div class="content-wrapper">

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

{{--<div id="wrapper">

    @include('partials.navbar')

    <div id="page-wrapper">

        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">
                    @yield('page-header')
                </h1>
            </div>
        </div>

        @include('partials.alerts')

        @if (($dominion = Request::route()->getParameter('dominion')) && Request::is('dominion/*') && !Request::is('dominion/*/status'))
            @include('partials.resources-overview')
        @endif

        @yield('content')

    </div>

</div>--}}

@include('partials.scripts')

</body>
</html>
