@if (isset($selectedDominion) && !Route::is('dominion.status'))
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-bar-chart"></i> {{ $selectedDominion->name }} Overview</h3>
        </div>
        <div class="box-body">

            <div class="row">
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Land:</b></div>
                        <div class="col-lg-6">{{ number_format($landCalculator->getTotalLand($selectedDominion)) }}</div>
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
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_ore) }}</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Networth:</b></div>
                        <div class="col-lg-6">{{ number_format($networthCalculator->getDominionNetworth($selectedDominion)) }}</div>
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
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_mana) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Gems:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->resource_gems) }}</div>
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
                        <div class="col-lg-6"><b>Draftees:</b></div>
                        <div class="col-lg-6">{{ number_format($selectedDominion->military_draftees) }}</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Spy Str<span class="hidden-xs">ength</span>:</b></div>
                        <div class="col-lg-6">{{ number_format(floor($selectedDominion->spy_strength)) }}%</div>
                    </div>
                </div>
                <div class="col-xs-3">
                    <div class="row">
                        <div class="col-lg-6"><b>Research:</b></div>
                        <div class="col-lg-6">{{ number_format(min($selectedDominion->resource_tech / 100, 100), 2) }}%</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endif
