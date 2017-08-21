<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-question-circle"></i> Consult advisor</h3>
    </div>
    <div class="box-body">
        <div class="row">

            {{-- todo: refactor this --}}

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.production') }}" class="btn btn-app btn-block">
                    <i class="fa fa-industry"></i> Production
                </a>
            </div>

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.military') }}" class="btn btn-app btn-block">
                    <i class="ra ra-sword"></i> Military
                </a>
            </div>

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.land') }}" class="btn btn-app btn-block">
                    <i class="ra ra-honeycomb"></i> Land
                </a>
            </div>

            <div class="col-xs-12 visible-xs">&nbsp;</div>

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.construction') }}" class="btn btn-app btn-block">
                    <i class="fa fa-home"></i> Construction
                </a>
            </div>

            <div class="col-xs-12 visible-sm">&nbsp;</div>

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.magic') }}" class="btn btn-app btn-block">
                    <i class="ra ra-burning-embers"></i> Magic
                </a>
            </div>

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.rankings') }}" class="btn btn-app btn-block">
                    <i class="fa fa-trophy"></i> Rankings
                </a>
            </div>

            <div class="col-xs-12 visible-xs visible-md">&nbsp;</div>

            <div class="col-xs-4 col-sm-3 col-md-2 col-lg-1">
                <a href="{{ route('dominion.advisors.statistics') }}" class="btn btn-app btn-block">
                    <i class="fa fa-bar-chart"></i> Statistics
                </a>
            </div>

        </div>
    </div>
</div>
