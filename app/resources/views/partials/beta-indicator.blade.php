@if (Request::getHttpHost() === 'test.opendominion.net')
    <div class="alert-warning" style="padding: 10px 15px; font-size: 12px;">
        <i class="icon fa fa-warning"></i> This is the OpenDominion test server, which is under active development. Bugs and occasional data loss are to be expected.
    </div>
@elseif (App::environment() === 'local')
    <div class="alert-danger" style="padding: 10px 15px; font-size: 12px;">
        <i class="icon fa fa-warning"></i> This instance of OpenDominion is running on a <b>local</b> environment and should not be used for production purposes.
    </div>
@endif
