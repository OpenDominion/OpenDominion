@if (Request::getHttpHost() === 'test.opendominion.net')
    <div class="alert alert-warning small border-0 rounded-0 p-2 mb-0">
        <i class="fa fa-warning me-1"></i> This is the OpenDominion test server, which is under active development. Bugs and occasional data loss are to be expected.
    </div>
@elseif (App::environment() === 'local')
    <div class="alert alert-danger small border-0 rounded-0 p-2 mb-0">
        <i class="fa fa-warning me-1"></i> This instance of OpenDominion is running on a <b>local</b> environment and should not be used for production purposes.
    </div>
@endif
