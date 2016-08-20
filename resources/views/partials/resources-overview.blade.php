<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="ra ra-capitol ra-rw"></i> {{ $dominion->name }} Overview
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-3">
                        <div class="row">
                            <div class="col-lg-6"><b>Networth:</b></div>
                            <div class="col-lg-6">NYI</div>
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="row">
                            <div class="col-lg-6"><b>Platinum:</b></div>
                            <div class="col-lg-6">{{ number_format($dominion->resource_platinum) }}</div>
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="row">
                            <div class="col-lg-6"><b>Food:</b></div>
                            <div class="col-lg-6">{{ number_format($dominion->resource_food) }}</div>
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
                            <div class="col-lg-6">{{ number_format($dominion->peasants) }}</div>
                        </div>
                    </div>
                    <div class="col-xs-3">
                        <div class="row">
                            <div class="col-lg-6"><b>Lumber:</b></div>
                            <div class="col-lg-6">{{ number_format($dominion->resource_lumber) }}</div>
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
    </div>
</div>
