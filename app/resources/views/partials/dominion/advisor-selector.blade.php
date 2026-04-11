@php
    $title = 'Consult advisor';
    $baseRoute = 'dominion.advisors.';
    if ($targetDominion != null) {
        $baseRoute = 'dominion.realm.advisors.';
        $title .= ' for '.$targetDominion->name;
    }
@endphp
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fa fa-question-circle"></i> {{ $title }}</span>
    </div>
    <div class="card-body text-center pb-0">

        <a href="{{ route($baseRoute.'op-center', $targetDominion) }}" class="btn btn-app">
            <i class="fa fa-globe"></i> Op Center
        </a>

        <a href="{{ route($baseRoute.'production', $targetDominion) }}" class="btn btn-app">
            <i class="fa fa-industry"></i> Production
        </a>

        <a href="{{ route($baseRoute.'military', $targetDominion) }}" class="btn btn-app">
            <i class="ra ra-sword"></i> Military
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
