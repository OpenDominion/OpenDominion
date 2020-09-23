@php
    $title = 'Consult advisor';
    $baseRoute = 'dominion.advisors.';
    if($targetDominion != null) {
        $baseRoute = 'dominion.realm.advisors.';
        $title .= ' for '.$targetDominion->name;
    }
@endphp
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-question-circle"></i> {{ $title }}</h3>
    </div>
    <div class="box-body text-center">

        @if($targetDominion == null)
            <a href="{{ route('dominion.realm.advisors.status', $targetDominion) }}" class="btn btn-app">
                <i class="fa fa-bar-chart"></i> Status
            </a>
        @else
            <a href="{{ route($baseRoute.'production', $targetDominion) }}" class="btn btn-app">
                <i class="fa fa-industry"></i> Production
            </a>
        @endif

        <a href="{{ route($baseRoute.'military', $targetDominion) }}" class="btn btn-app">
            <i class="ra ra-sword"></i> Military
        </a>

        <a href="{{ route($baseRoute.'land', $targetDominion) }}" class="btn btn-app">
            <i class="ra ra-honeycomb"></i> Land
        </a>

        <a href="{{ route($baseRoute.'construct', $targetDominion) }}" class="btn btn-app">
            <i class="fa fa-home"></i> Construction
        </a>


        <a href="{{ route($baseRoute.'castle', $targetDominion) }}" class="btn btn-app">
            <i class="ra ra-tower"></i> Castle
        </a>

        <a href="{{ route($baseRoute.'magic', $targetDominion) }}" class="btn btn-app">
            <i class="ra ra-burning-embers"></i> Magic
        </a>

        <a href="{{ route($baseRoute.'rankings', $targetDominion) }}" class="btn btn-app">
            <i class="fa fa-trophy"></i> Rankings
        </a>

        <a href="{{ route($baseRoute.'statistics', $targetDominion) }}" class="btn btn-app">
            <i class="fa fa-bar-chart"></i> Statistics
        </a>

    </div>
</div>
