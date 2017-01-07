@if (App::environment() !== 'production')
    <div class="alert alert-warning alert-dismissible">
        <button class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h4><i class="icon fa fa-warning"></i> Heads up!</h4>
        <p>This installation of OpenDominion is running on <b>{{ App::environment() }}</b> environment and is not meant for production purposes. Any data you register and actions you take on this instance might be wiped without notice.</p>
    </div>
@endif

@if (!$errors->isEmpty())
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-danger">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <p>One or more errors occurred:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@foreach (['danger', 'warning', 'success', 'info'] as $alert_type)
    @if (Session::has('alert-' . $alert_type))
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-{{ $alert_type }}">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <p>{{ Session::get('alert-' . $alert_type) }}</p>
                </div>
            </div>
        </div>
    @endif
@endforeach
