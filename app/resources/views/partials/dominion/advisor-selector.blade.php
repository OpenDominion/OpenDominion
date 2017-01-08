<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-group fa-fw"></i> Choose advisor to consult</h3>
    </div>
    <div class="box-body">
        <div class="row">

            <div class="col-xs-6 col-sm-3">
                <a href="{{ route('dominion.advisors.production') }}" class="btn btn-app btn-block">
                    <i class="fa fa-industry"></i> Production
                </a>
                <div class="spacer"></div>
            </div>

            <div class="col-xs-6 col-sm-3">
                <a href="{{ route('dominion.advisors.military') }}" class="btn btn-app btn-block">
                    <i class="ra ra-crossed-swords"></i> Military
                </a>
            </div>

            <div class="col-xs-6 col-sm-3">
                <a href="{{ route('dominion.advisors.land') }}" class="btn btn-app btn-block">
                    <i class="ra ra-honeycomb"></i> Land
                </a>
            </div>

            <div class="col-xs-6 col-sm-3">
                <a href="{{ route('dominion.advisors.construction') }}" class="btn btn-app btn-block">
                    <i class="fa fa-home"></i> Construction
                </a>
            </div>

        </div>
    </div>
</div>
