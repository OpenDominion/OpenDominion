@if (isset($selectedDominion) && !Route::is('dominion.status'))
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="ra ra-capitol ra-fw"></i> {{ $selectedDominion->name }} Overview</h3>
        </div>
        <div class="box-body">

            <div class="row">
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Networth:</b></div>
                        <div class="col-lg-6">{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Platinum:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_platinum) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Food:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_food) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Ore:</b></div>
                        <div class="col-lg-6">NYI</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Peasants:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->peasants) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Lumber:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_lumber) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Mana:</b></div>
                        <div class="col-lg-6">NYI</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Gems:</b></div>
                        <div class="col-lg-6">NYI</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endif
