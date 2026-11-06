@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if ($hero === null)
                <form action="{{ route('dominion.heroes.create') }}" method="post" role="form">
                    @csrf
                    <div class="card card-primary">
                    <div class="card-header">
                        <span class="card-title"><i class="ra ra-knight-helmet"></i> Heroes</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Name</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input name="name" id="name" class="form-control" />
                                            <button id="randomize" class="btn btn-secondary" type="button">Randomize</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label">Class</label>
                                    <div class="col-sm-9">
                                        <select name="class" class="form-select">
                                            @foreach ($heroHelper->getBasicClasses() as $class)
                                                <option value="{{ $class['key'] }}">
                                                    {{ $class['name'] }} - {{ str_replace('_', ' ', $class['perk_type']) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Create Hero</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Level Bonuses</span>
                    </div>
                    <div class="card-body table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Level</th>
                                        <th>XP</th>
                                        @foreach ($heroHelper->getClasses() as $class)
                                            <th>{{ $class['name'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($heroCalculator->getExperienceLevels() as $level)
                                        @if ($level['level'] !== 0)
                                            <tr>
                                                <td>{{ $level['level'] }}</td>
                                                <td>{{ $level['xp'] }}</td>
                                                @foreach ($heroHelper->getClasses() as $class)
                                                    <th>{{ number_format($heroCalculator->calculatePassiveBonus($class['perk_type'], $level['level']), 2) }}%</th>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                    </div>
                </div>
                </form>
            @else
                <div class="card card-primary">
                    <div class="card-header">
                        <span class="card-title"><i class="ra ra-knight-helmet"></i> Heroes</span>
                    </div>
                    <div class="card-body">
                        @php
                            $heroLevel = $heroCalculator->getHeroLevel($hero);
                            $unlockedUpgrades = $hero->upgrades->pluck('key')->all();
                            $upgradeTypesByLevel = $heroHelper->getUpgradeTypesByLevel($hero->class);
                            $currentLevelXp = $heroCalculator->getCurrentLevelXP($hero);
                            $nextLevelXp = $heroCalculator->getNextLevelXP($hero);
                            $isMaxLevel = $heroLevel >= $heroCalculator->getExperienceLevels()->max('level');
                            $levelProgress = $isMaxLevel ? 100 : min(100, max(0, ($hero->experience - $currentLevelXp) / ($nextLevelXp - $currentLevelXp) * 100));
                            $dominionSlots = [];
                            foreach ($heroCalculator->getUpgradeSlots($hero) as $level => $upgrade) {
                                $dominionSlots[$level] = ['upgrade' => $upgrade, 'types' => $upgradeTypesByLevel[$level] ?? []];
                            }
                            $dominionUpgrades = $heroHelper->getHeroUpgradesByClass($hero->class)->where('active', true);
                            $loadoutSlotTypes = \OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot::cases();
                            $loadout = $heroCalculator->getLoadout($hero);
                            $loadoutStatBonuses = $heroCalculator->getLoadoutStatBonuses($hero);
                            $availableLoadoutOptions = $heroCalculator->getAvailableLoadoutOptions($hero);
                            $equippedLoadoutKeys = collect($loadout)->flatten()->pluck('key')->all();
                            $loadoutTiles = [];
                            foreach ($loadoutSlotTypes as $loadoutSlot) {
                                for ($index = 0; $index < $loadoutSlot->capacity(); $index++) {
                                    $loadoutTiles[] = [
                                        'slot' => $loadoutSlot,
                                        'option' => $loadout[$loadoutSlot->value][$index] ?? null,
                                        'available' => $index < $heroCalculator->getLoadoutSlotCapacity($hero, $loadoutSlot) && $heroCalculator->getAvailableLoadoutOptions($hero, $loadoutSlot) !== [],
                                        'enabled' => $heroCalculator->isLoadoutSlotEnabled($loadoutSlot),
                                        'index' => $index,
                                    ];
                                }
                            }
                            $passiveBonusMultiplier = $heroCalculator->getPassiveBonusMultiplier($selectedDominion);
                            $playedClasses = [];
                            $unplayedClasses = [];
                            foreach ($heroHelper->getClasses() as $class) {
                                $isActiveClass = $hero->class == $class['key'];
                                $data = array_get($heroClassData, $class['key'], []);
                                $experience = $isActiveClass ? $hero->experience : array_get($data, 'experience', 0);
                                $row = [
                                    'class' => $class,
                                    'active' => $isActiveClass,
                                    'level' => $isActiveClass ? $heroLevel : (array_get($data, 'level') ?? $heroCalculator->getExperienceLevel($experience)),
                                    'description' => $heroCalculator->getPassiveDescription($hero, $class['perk_type']),
                                    'baseBonus' => $passiveBonusMultiplier > 1 ? $heroCalculator->getPassiveBonus($hero, $class['perk_type']) : 0,
                                ];
                                if ($isActiveClass || $experience > 0) {
                                    $playedClasses[] = $row;
                                } else {
                                    $unplayedClasses[] = $row;
                                }
                            }
                        @endphp
                        <div class="hero-banner">
                            <div class="hero-medallion" title="Class: {{ $heroHelper->getClassDisplayName($hero->class) }}" data-bs-toggle="tooltip">
                                <i class="ra {{ $heroHelper->getClassIcon($hero->class) }}"></i>
                            </div>
                            <div class="hero-identity">
                                <div class="hero-title">
                                    <span class="hero-name">{{ $hero->name }}</span>
                                    <span class="hero-subtitle">Level {{ $heroLevel }} {{ $heroHelper->getClassDisplayName($hero->class) }}</span>
                                </div>
                                <div class="hero-xp-bar"><div style="width: {{ round($levelProgress, 1) }}%"></div></div>
                                <div class="hero-xp-text">
                                    @if ($isMaxLevel)
                                        {{ number_format(rfloor($hero->experience)) }} XP · Max level
                                    @else
                                        {{ number_format(rfloor($hero->experience)) }} / {{ number_format($nextLevelXp) }} XP · {{ number_format(ceil($nextLevelXp - $hero->experience)) }} to level {{ $heroLevel + 1 }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mt-0">
                            <div class="col-xl-6">
                                <div class="hero-section-head"><i class="ra ra-tower"></i> Dominion Bonuses</div>
                                <div class="hero-tiles">
                                    @foreach ($hero->upgrades->where('type', 'directive') as $upgrade)
                                        @include('partials.dominion.hero-upgrade-tile', ['upgrade' => $upgrade, 'level' => $upgrade->level, 'types' => [$upgrade->type]])
                                    @endforeach
                                    @foreach ($dominionSlots as $level => $slot)
                                        @include('partials.dominion.hero-upgrade-tile', ['upgrade' => $slot['upgrade'], 'level' => $level, 'types' => $slot['types']])
                                    @endforeach
                                </div>

                                <div class="hero-sub-label">Class bonuses</div>
                                <div class="hero-classes">
                                    @foreach ($playedClasses as $row)
                                        @include('partials.dominion.hero-class-row', ['row' => $row])
                                    @endforeach
                                    @if ($unplayedClasses)
                                        <div class="collapse" id="hero-unplayed-classes">
                                            @foreach ($unplayedClasses as $row)
                                                @include('partials.dominion.hero-class-row', ['row' => $row])
                                            @endforeach
                                        </div>
                                        <button type="button" class="hero-classes-toggle collapsed" data-bs-toggle="collapse" data-bs-target="#hero-unplayed-classes" aria-expanded="false" aria-controls="hero-unplayed-classes">
                                            <span class="when-collapsed">Show {{ count($unplayedClasses) }} more classes</span>
                                            <span class="when-expanded">Show fewer classes</span>
                                        </button>
                                    @endif
                                </div>
                                @if ($passiveBonusMultiplier > 1)
                                    <div class="hero-classes-note">
                                        Your hero bonuses are being increased by {{ number_format(($passiveBonusMultiplier - 1) * 100, 2) }}%
                                    </div>
                                @endif
                            </div>

                            <div class="col-xl-6">
                                <div class="hero-section-head"><i class="ra ra-axe"></i> Hero Battles</div>
                                <div class="hero-tiles">
                                    @foreach ($loadoutTiles as $tile)
                                        @include('partials.dominion.hero-loadout-tile', $tile)
                                    @endforeach
                                </div>
                                <div class="hero-stats">
                                    <div class="hero-stat hero-stat-rating" title="PvP rating, with your Win - Loss - Draw record" data-bs-toggle="tooltip">
                                        <div class="hero-stat-label">Rating</div>
                                        <div class="hero-stat-value">{{ $hero->combat_rating }}</div>
                                        <div class="hero-stat-change">{{ sprintf('%s-%s-%s', $hero->stat_combat_wins, $hero->stat_combat_losses, $hero->stat_combat_draws) }}</div>
                                    </div>
                                    @foreach ($heroCalculator->getHeroCombatStats($hero) as $stat => $value)
                                        @php($statBonus = $loadoutStatBonuses[$stat] ?? 0)
                                        <div class="hero-stat" title="{{ $heroHelper->getCombatStatTooltip($stat) }}" data-bs-toggle="tooltip">
                                            <div class="hero-stat-label">{{ ucwords($stat) }}</div>
                                            <div class="hero-stat-value">{{ $value }}</div>
                                            <div @class(['hero-stat-change', 'text-green' => $statBonus > 0, 'text-red' => $statBonus < 0])>{{ $statBonus ? sprintf('%+g', $statBonus) : '' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('dominion.heroes') }}" method="post" role="form" class="disable-after-click" id="hero-upgrades">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title"><i class="ra ra-tower me-1"></i> Hero Upgrades</span>
                        </div>
                        <div class="card-body table-responsive">
                            <table class="table hero-upgrades-table">
                                <colgroup>
                                    <col width="40">
                                    <col width="45">
                                    <col width="200">
                                    <col width="60">
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Level</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                @foreach ($dominionUpgrades as $upgrade)
                                    <tr class="hero-upgrade {{ $heroCalculator->canUnlockUpgrade($hero, $upgrade) ? 'text-default' : 'text-muted' }}" data-level="{{ $upgrade->type === 'directive' ? '--' : $upgrade->level }}">
                                        <td class="text-center{{ in_array($upgrade->key, $unlockedUpgrades) ? ' text-green' : null }}">
                                            @if (in_array($upgrade->key, $unlockedUpgrades))
                                                <i class="fa fa-check"></i>
                                            @else
                                                <input type="radio" class="form-check-input" name="key" id="upgrade_{{ $upgrade->key }}" value="{{ $upgrade->key }}" {{ $heroCalculator->canUnlockUpgrade($hero, $upgrade) ? null : 'disabled' }}>
                                            @endif
                                        </td>
                                        <td>
                                            <i class="ra ra-fw {{ $upgrade->icon }}" style="font-size: 24px;" title="{{ ucwords($upgrade->type) }}" data-bs-toggle="tooltip"></i>
                                        </td>
                                        <td>
                                            <label for="upgrade_{{ $upgrade->key }}" style="font-weight: normal;">
                                                {{ $upgrade->name }}
                                            </label>
                                        </td>
                                        <td>
                                            {{ $upgrade->type === 'directive' ? '--' : $upgrade->level }}
                                        </td>
                                        <td>
                                            <label for="upgrade_{{ $upgrade->key }}" style="font-weight: normal;">
                                                {!! $heroHelper->getUpgradeDescription($upgrade, '<br/>') !!}
                                            </label>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" {{ (!$heroCalculator->getUnlockableUpgradeCount($hero) || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
                        </div>
                    </div>
                </form>

                <form action="{{ route('dominion.heroes.loadout') }}" method="post" role="form" class="disable-after-click" id="hero-loadout">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title"><i class="ra ra-axe me-1"></i> Combat Loadout</span>
                        </div>
                        <div class="card-body table-responsive">
                            <p>Your hero brings this loadout into every battle, including when a realmmate borrows your hero. You can change it at any time; battles already underway are not affected.</p>
                            @foreach ($loadoutSlotTypes as $loadoutSlot)
                                @if (!$heroCalculator->isLoadoutSlotEnabled($loadoutSlot))
                                    <div class="hero-sub-label">{{ $loadoutSlot->label() }} (coming soon)</div>
                                    @continue
                                @endif
                                @php($isMultiple = $loadoutSlot->capacity() > 1)
                                @php($slotCapacity = $heroCalculator->getLoadoutSlotCapacity($hero, $loadoutSlot))
                                <div class="hero-sub-label">{{ $loadoutSlot->label() }} ({{ $slotCapacity > 1 ? 'choose up to ' . $slotCapacity : 'choose 1' }}{{ $slotCapacity < $loadoutSlot->capacity() ? '; ' . strtolower($heroHelper->getLoadoutSlotRequirement($loadoutSlot, $slotCapacity)) . ' for another' : '' }})</div>
                                <table class="table hero-upgrades-table">
                                    <colgroup>
                                        <col width="40">
                                        <col width="45">
                                        <col width="200">
                                        <col>
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th>Name</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    @if (!$isMultiple)
                                        <tr class="hero-loadout-option" data-slot="{{ $loadoutSlot->value }}">
                                            <td class="text-center">
                                                <input type="radio" class="form-check-input" name="{{ $loadoutSlot->value }}" id="loadout_{{ $loadoutSlot->value }}_none" value="" {{ $loadout[$loadoutSlot->value] === [] ? 'checked' : null }}>
                                            </td>
                                            <td></td>
                                            <td colspan="2">
                                                <label for="loadout_{{ $loadoutSlot->value }}_none" style="font-weight: normal;">None</label>
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach ($heroHelper->getLoadoutOptions($loadoutSlot) as $option)
                                        @php($isAvailable = isset($availableLoadoutOptions[$option->key]))
                                        <tr class="hero-loadout-option {{ $isAvailable ? 'text-default' : 'text-muted' }}" data-slot="{{ $loadoutSlot->value }}" {{ $isAvailable ? 'data-available' : null }}>
                                            <td class="text-center">
                                                <input
                                                    type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                                                    class="form-check-input"
                                                    name="{{ $loadoutSlot->value }}{{ $isMultiple ? '[]' : null }}"
                                                    id="loadout_{{ $option->key }}"
                                                    value="{{ $option->key }}"
                                                    {{ in_array($option->key, $equippedLoadoutKeys) ? 'checked' : null }}
                                                    {{ $isAvailable ? null : 'disabled data-locked' }}
                                                >
                                            </td>
                                            <td>
                                                <i class="ra ra-fw {{ $option->icon }}" style="font-size: 24px;" title="{{ $loadoutSlot->label() }}" data-bs-toggle="tooltip"></i>
                                            </td>
                                            <td>
                                                <label for="loadout_{{ $option->key }}" style="font-weight: normal;">
                                                    {{ $heroHelper->getLoadoutOptionName($option) }}
                                                </label>
                                            </td>
                                            <td>
                                                <label for="loadout_{{ $option->key }}" style="font-weight: normal;">
                                                    {{ $heroHelper->getLoadoutOptionDescription($option) }}
                                                    @include('partials.dominion.hero-rules-tooltip', ['option' => $option])
                                                    @if (!$isAvailable || $heroHelper->isClassLoadoutOption($option))
                                                        <br/><small class="text-muted">{{ $heroHelper->getLoadoutOptionRequirement($option) }}</small>
                                                    @endif
                                                </label>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endforeach
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Save Loadout</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Information</span>
                </div>
                <div class="card-body">
                    <p>Your hero gains experience and levels up, increasing its class bonuses and unlocking new upgrades.</p>
                    <p>Your hero gains 1 XP per acre gained from invasion (against dominions at least 75% of your size), 0.8 XP per acre explored, 0.5 XP per spy strength spent on successful espionage operations (excluding theft), and 1 XP per wizard strength spent on successful magic operations.</p>
                    <p>Your hero loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.</p>
                    <p>You can change your hero class at any time, but you'll pay a penalty of {{ \OpenDominion\Calculators\Dominion\HeroCalculator::CLASS_CHANGE_XP_PENALTY }} XP. Any bonuses you've acquired from other classes will be halved while inactive.</p>
                    <p>Advanced hero classes have special requirements to select and unlock additional upgrades on first use. All hero upgrades are <b>permanent</b>, but your combat loadout can be changed at any time.</p>
                    <p>There is a {{ \OpenDominion\Calculators\Dominion\HeroCalculator::CLASS_CHANGE_COOLDOWN_HOURS }} hour cooldown between class changes.</p>
                    @if ($hero && !$heroCalculator->canChangeClass($hero))
                        <p>You cannot change your hero class for another {{ $heroCalculator->hoursUntilClassChange($hero) }} hours.</p>
                    @endif
                </div>
            </div>

            @if ($hero)
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Level Bonuses</span>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Level</th>
                                    <th>XP</th>
                                    <th>{{ $heroHelper->getClassDisplayName($hero->class) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($heroCalculator->getExperienceLevels() as $level)
                                    @if ($level['level'] !== 0)
                                        <tr class="{{ $heroCalculator->getHeroLevel($hero) == $level['level'] ? 'table-active' : null }}">
                                            <td>{{ $level['level'] }}</td>
                                            <td>{{ number_format($level['xp']) }}</td>
                                            <td>{{ number_format($heroCalculator->calculatePassiveBonus($heroHelper->getPassivePerkType($hero->class), $level['level']), 2) }}%</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            const names = @json($heroHelper->getNamesByRace($selectedDominion->race->key));

            var buttonElement = $('#randomize'),
                nameElement = $('#name');

            function randomizeName() {
                var random = names[Math.floor(Math.random() * names.length)];
                nameElement.val(random);
            }

            buttonElement.click(function() {
                randomizeName();
            });

            var upgradeRadios = $('input[type=radio][name=key]');
            upgradeRadios.change(function() {
                $('.hero-upgrade').removeClass('danger');
                var level = $(this).parent().parent().data('level');
                $('.hero-upgrade[data-level='+level+']').addClass('danger');
                $(this).parent().parent().removeClass('danger');
            });

            $('.hero-tile-open[data-upgrade-level]').click(function (event) {
                var rows = $('.hero-upgrade[data-level=' + $(this).data('upgrade-level') + ']');
                if (!rows.length) {
                    return;
                }
                event.preventDefault();
                rows[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                rows.removeClass('hero-upgrade-highlight');
                setTimeout(function () {
                    rows.addClass('hero-upgrade-highlight');
                });
            });

            $('.hero-tile-open[data-loadout-slot]').click(function (event) {
                var rows = $('.hero-loadout-option[data-available][data-slot=' + $(this).data('loadout-slot') + ']');
                if (!rows.length) {
                    return;
                }
                event.preventDefault();
                rows[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                rows.removeClass('hero-upgrade-highlight');
                setTimeout(function () {
                    rows.addClass('hero-upgrade-highlight');
                });
            });

            var gearCheckboxes = $('input[type=checkbox][name="gear[]"]');
            var maximumGear = {{ isset($hero) ? $heroCalculator->getLoadoutSlotCapacity($hero, \OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot::Gear) : 0 }};

            function limitGear() {
                var atLimit = gearCheckboxes.filter(':checked').length >= maximumGear;
                gearCheckboxes.not('[data-locked]').not(':checked').prop('disabled', atLimit);
            }

            gearCheckboxes.change(limitGear);
            limitGear();

            randomizeName();
        })(jQuery);
    </script>
@endpush
