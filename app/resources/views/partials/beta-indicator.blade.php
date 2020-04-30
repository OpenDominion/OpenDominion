@if (Request::getHttpHost() === 'beta.opendominion.net')
    <div class="alert-warning" style="padding: 10px 15px; font-size: 12px;">
        <i class="icon fa fa-warning"></i> This is the open beta of OpenDominion. As the game is still in development, not all features are present and some bugs may occur.
    </div>
@elseif (App::environment() === 'local')
    <div class="alert-danger" style="padding: 10px 15px; font-size: 12px;">
        <i class="icon fa fa-warning"></i> This instance of OpenDominion is running on a <b>local</b> environment and should not be used for production purposes.
    </div>
@endif
