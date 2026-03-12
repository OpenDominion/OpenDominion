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

<div class="wrapper">

    <!-- Header -->
    <header class="main-header">
        <nav class="navbar navbar-static-top">
            <div class="container">

                <!-- Navbar Header -->
                <div class="navbar-header">
                    <a href="{{ url('') }}" class="navbar-brand">Open<b>Dominion</b></a>
                    <button class="navbar-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#navbar-collapse">
                        <i class="fa fa-bars"></i>
                    </button>
                </div>

                <!-- Navbar Left Menu -->
                <div class="collapse navbar-collapse float-start" id="navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="table-active"><a href="{{ route('home') }}">Error 503 <span class="sr-only">(current)</span></a></li>
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
                    <div class="col-sm-8 offset-sm-2">

                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Status 503: Service Unavailable</h3>
                            </div>
                            <div class="card-body">
                                <p>
                                    OpenDominion is currently down for maintenance.
                                </p>
                                <p>
                                    <dl>
                                        <dt>Message:</dt>
                                        <dd>{{ $exception->getMessage() }}</dd>
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
