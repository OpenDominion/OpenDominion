@if (isset($selectedDominion) && !Route::is('dominion.status'))
    @php
        $resources = [
            'Defense' => number_format($militaryCalculator->getDefensivePower($selectedDominion)),
            'Draftees' => number_format($selectedDominion->military_draftees),
            'Employment' => number_format($populationCalculator->getEmploymentPercentage($selectedDominion), 2)."%",
            'Food' => number_format($selectedDominion->resource_food),
            'Gems' => number_format($selectedDominion->resource_gems),
            'Jobs' => number_format($populationCalculator->getEmploymentJobs($selectedDominion) - $selectedDominion->peasants),
            'Land' => number_format($landCalculator->getTotalLand($selectedDominion)),
            'Lumber' => number_format($selectedDominion->resource_lumber),
            'Mana' => number_format($selectedDominion->resource_mana),
            'Morale' => number_format($selectedDominion->morale)."%",
            'Networth' => number_format($selectedDominion->calculated_networth),
            'Ore' => number_format($selectedDominion->resource_ore),
            'Peasants' => number_format($selectedDominion->peasants),
            'Platinum' => number_format($selectedDominion->resource_platinum),
            'Prestige' => number_format($selectedDominion->prestige),
            'Research' => number_format(min($selectedDominion->resource_tech / $techCalculator->getTechCost($selectedDominion) * 100, 100), 2)."%",
            'Spy Ratio' => number_format($militaryCalculator->getSpyRatio($selectedDominion), 3),
            'Spy Str' => number_format($selectedDominion->spy_strength)."%",
            'Wiz Ratio' => number_format($militaryCalculator->getWizardRatio($selectedDominion), 3),
            'Wiz Str' => number_format($selectedDominion->wizard_strength)."%",
        ];
        $config = $miscHelper->getResourceOverviewDefaultSettings();
        if (isset($selectedDominion->settings['resources_overview'])) {
            $config = $selectedDominion->settings['resources_overview'];
        }
    @endphp
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <span class="card-title flex-grow-1"><i class="fa fa-bar-chart"></i> Overview - {{ $selectedDominion->name }}</span>
            <a class="btn btn-sm btn-outline-secondary flex-shrink-0 dominion-settings-button" href="{{ route('dominion.misc.settings') }}" aria-label="Dominion settings" title="Dominion Settings" data-bs-toggle="tooltip" data-bs-placement="left">
                <i class="fa fa-sliders me-sm-1" aria-hidden="true"></i>
                <span class="d-none d-sm-inline">Settings</span>
            </a>
        </div>
        <div class="card-body small">
            @foreach ($config as $row)
                @if (isset($row) && is_array($row))
                    <div class="row">
                        @foreach ($row as $column)
                            @if (isset($resources[$column]))
                                <div class="col-3">
                                    <div class="row">
                                        <div class="col-lg-6 text-nowrap"><b>{{ $column }}:</b></div>
                                        <div class="col-lg-6">{{ $resources[$column] }}</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
