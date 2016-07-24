@if (App::environment() !== 'production')
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-warning">
                <p><strong>Note:</strong> This installation of OpenDominion is running on
                    <b>{{ App::environment() }}</b> environment and is not meant for production purposes. Any data you
                    register and actions you take on this instance might be wiped without notice.</p>
            </div>
        </div>
    </div>
@endif

@if ($errors->has())
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-danger">
                <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                @if ($errors->count() > 1)
                    <p>The following <strong>errors</strong> occurred:</p>
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                @else
                    <p><strong>Error:</strong> {{ $errors->first() }}</p>
                @endif
            </div>
        </div>
    </div>
@endif
