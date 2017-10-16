@if (Request::getHttpHost() === 'beta.opendominion.net')
    <div class="bg-yellow" style="padding: 10px 15px; z-index: 999999; font-size: 12px; font-weight: 400; color: #fff;">
        <i class="icon fa fa-warning"></i> This is the open beta of OpenDominion. As the game is still in development, not all features are present and some bugs may occur.
    </div>
@elseif (App::environment() === 'local')
    <div class="bg-red" style="padding: 10px 15px; z-index: 999999; font-size: 12px; font-weight: 400; color: #fff;">
        <i class="icon fa fa-warning"></i> This instance of OpenDominion is running on a <b>local</b> environment and should not be used for production purposes.
    </div>
@endif
