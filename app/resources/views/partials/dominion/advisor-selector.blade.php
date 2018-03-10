<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-question-circle"></i> Consult advisor</h3>
    </div>
    <div class="box-body text-center">

        <a href="{{ route('dominion.advisors.production') }}" class="btn btn-app">
            <i class="fa fa-industry"></i> Production
        </a>

        <a href="{{ route('dominion.advisors.military') }}" class="btn btn-app">
            <i class="ra ra-sword"></i> Military
        </a>

        <a href="{{ route('dominion.advisors.land') }}" class="btn btn-app">
            <i class="ra ra-honeycomb"></i> Land
        </a>

        <a href="{{ route('dominion.advisors.construct') }}" class="btn btn-app">
            <i class="fa fa-home"></i> Construction
        </a>

        <a href="{{ route('dominion.advisors.magic') }}" class="btn btn-app">
            <i class="ra ra-burning-embers"></i> Magic
        </a>

        {{--<a href="{{ route('dominion.advisors.rankings', 'land') }}" class="btn btn-app">
            <i class="fa fa-trophy"></i> Rankings
        </a>--}}

        <a href="{{ route('dominion.advisors.statistics') }}" class="btn btn-app">
            <i class="fa fa-bar-chart"></i> Statistics
        </a>

    </div>
</div>
