@if (Request::getHttpHost() !== 'dev.opendominion.wavehack.net')
    <div style="padding: 10px 15px; background: rgb(243, 156, 18); z-index: 999999; font-size: 12px; font-weight: 400; color: #fff;">
        <i class="icon fa fa-warning"></i> This is the open beta of OpenDominion. As the game is still in development, not all features are present and some bugs may occur.
    </div>
@elseif (App::environment() === 'local')
    <div style="padding: 10px 15px; background: rgb(243, 156, 18); z-index: 999999; font-size: 12px; font-weight: 400; color: #fff;">
        <i class="icon fa fa-warning"></i> This instance of OpenDominion is running on a <b>local</b> environment and should not be used for production purposes.
    </div>
@endif

