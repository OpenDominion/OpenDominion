@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if ($hero === null)
                <form class="form-horizontal" action="{{ route('dominion.heroes.create') }}" method="post" role="form">
                    @csrf
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Heroes</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Name</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input name="name" id="name" class="form-control" />
                                                <div class="input-group-btn">
                                                    <button id="randomize" class="btn btn-default" type="button">Randomize</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Class</label>
                                        <div class="col-sm-9">
                                            <select name="class" class="form-control">
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
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Create Hero</button>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Level Bonuses</h3>
                        </div>
                        <div class="box-body table-responsive">
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
                <form action="{{ route('dominion.heroes') }}" method="post" role="form">
                    @csrf
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Heroes</h3>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                @php
                                    $heroClass = $heroHelper->getClasses()[$hero->class];
                                    $perkType = $heroClass['perk_type'];
                                    $upgrades = $hero->upgrades->groupBy('level');
                                    $unlockedUpgrades = $hero->upgrades->pluck('key')->all();
                                    $heroLevel = $heroCalculator->getHeroLevel($hero);
                                    $baseCombatStats = $heroCalculator->getBaseCombatStats($heroLevel);
                                @endphp
                                <div class="col-sm-12 col-lg-6">
                                    <div class="text-center" style="font-size: 24px;">
                                        {{ $hero->name }}
                                    </div>
                                    <div class="text-center">
                                        Level {{ $heroCalculator->getHeroLevel($hero) }} {{ $heroHelper->getClassDisplayName($hero->class) }}
                                    </div>
                                    <div class="text-center">
                                        {{ rfloor($hero->experience) }} / {{ $heroCalculator->getNextLevelXP($hero) }} XP
                                    </div>
                                    <div class="text-center">
                                        {{ $heroCalculator->getPassiveDescription($hero) }}
                                    </div>
                                    <div class="row" style="margin-top: 10px;">
                                        <div class="col-sm-12 col-md-6">
                                            <div class="text-center text-bold" style="margin: 7px 0 -8px 0;">
                                                Upgrades
                                            </div>
                                            <div class="row" style="font-size: 64px; margin-top: 20px;">
                                                <div class="col-xs-6 text-center">
                                                    <i class="hero-icon ra ra-fw {{ $heroHelper->getClassIcon($hero->class) }}" title="Current Class: {{ $heroHelper->getClassDisplayName($hero->class) }}" data-toggle="tooltip"></i>
                                                </div>
                                                @if (isset($upgrades[0]))
                                                    @foreach ($upgrades[0] as $upgrade)
                                                        <div class="col-xs-6 text-center">
                                                            {!! $heroHelper->getUpgradeIcon($upgrade) !!}
                                                        </div>
                                                    @endforeach
                                                @endif
                                                @if (isset($upgrades[2]))
                                                    @foreach ($upgrades[2] as $upgrade)
                                                        <div class="col-xs-6 text-center">
                                                            {!! $heroHelper->getUpgradeIcon($upgrade) !!}
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="col-xs-6 text-center">
                                                        {!! $heroHelper->getLockIcon(2) !!}
                                                    </div>
                                                @endif
                                                @if (isset($upgrades[4]))
                                                    @foreach ($upgrades[4] as $upgrade)
                                                        <div class="col-xs-6 text-center">
                                                            {!! $heroHelper->getUpgradeIcon($upgrade) !!}
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="col-xs-6 text-center">
                                                        {!! $heroHelper->getLockIcon(4) !!}
                                                    </div>
                                                @endif
                                                @if (isset($upgrades[6]))
                                                    @foreach ($upgrades[6] as $upgrade)
                                                        <div class="col-xs-6 text-center">
                                                            {!! $heroHelper->getUpgradeIcon($upgrade) !!}
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="col-xs-6 text-center">
                                                        {!! $heroHelper->getLockIcon(6) !!}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <table class="table table-condensed">
                                                <thead>
                                                    <tr>
                                                        <th colspan=2 class="text-center">Combat Stats</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <span title="PvP Rating" data-toggle="tooltip">Rating</span>
                                                        </td>
                                                        <td>
                                                            {{ $hero->combat_rating }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <span title="Win - Loss - Draw" data-toggle="tooltip">Record</span>
                                                        </td>
                                                        <td>
                                                            {{ sprintf('%s-%s-%s', $hero->stat_combat_wins, $hero->stat_combat_losses, $hero->stat_combat_draws) }}
                                                        </td>
                                                    </tr>
                                                    @foreach ($heroCalculator->getHeroCombatStats($hero) as $stat => $value)
                                                        <tr>
                                                            <td>
                                                                <span data-toggle="tooltip" title="{{ $heroHelper->getCombatStatTooltip($stat) }}">
                                                                    {{ ucwords($stat) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="{{ $baseCombatStats[$stat] != $value ? 'text-green' : null }}">
                                                                    {{ $value }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 col-lg-6">
                                    <div class="table-responsive" style="margin-top: 15px;">
                                        <table class="table table-condensed table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Level</th>
                                                    <th>XP</th>
                                                    <th>{{ $heroHelper->getClassDisplayName($hero->class) }} Bonus</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($heroCalculator->getExperienceLevels() as $level)
                                                    @if ($level['level'] !== 0)
                                                        <tr class="{{ $heroCalculator->getHeroLevel($hero) == $level['level'] ? 'active' : null }}">
                                                            <td>{{ $level['level'] }}</td>
                                                            <td>{{ $level['xp'] }}</td>
                                                            <td>{{ number_format($heroCalculator->calculatePassiveBonus($perkType, $level['level']), 2) }}%</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <h4>Hero Bonuses</h4>
                                    <div class="table-responsive">
                                        <table class="table table-condensed table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Class Name</th>
                                                    <th>Level</th>
                                                    <th>XP</th>
                                                    <th>Current Bonus</th>
                                                    <th>Switch Class</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($heroHelper->getClasses() as $class)
                                                    @if ($hero->class == $class['key'])
                                                        <tr class="active">
                                                            <td>{{ $heroHelper->getClassDisplayName($hero->class) }}</td>
                                                            <td>{{ $heroCalculator->getHeroLevel($hero) }}</td>
                                                            <td>{{ $hero->experience }}</td>
                                                            <td>{{ $heroCalculator->getPassiveDescription($hero) }}</td>
                                                            <td>
                                                                <span class="btn btn-primary btn-xs" disabled>
                                                                    Select
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @else
                                                        @php
                                                            $data = array_get($heroClassData, $class['key'], []);
                                                            $key = array_get($data, 'key', $class['key']);
                                                            $level = array_get($data, 'level', null);
                                                            $experience = array_get($data, 'experience', 0);
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $heroHelper->getClassDisplayName($key) }}</td>
                                                            <td>{{ $level ?? $heroCalculator->getExperienceLevel($experience) }}</td>
                                                            <td>{{ $experience }}</td>
                                                            <td>{{ $heroCalculator->getPassiveDescription($hero, $class['perk_type']) }}</td>
                                                            <td>
                                                                <a href="{{ route('dominion.heroes.change-class', $class['key']) }}" class="btn btn-primary btn-xs" {{ !$heroCalculator->canChangeClass($hero) ? 'disabled' : null }}>
                                                                    Select
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                @if ($heroCalculator->getPassiveBonusMultiplier($selectedDominion) > 1)
                                                    <tr>
                                                        <td colspan=5>
                                                            Your hero bonuses are being increased by {{ number_format($heroCalculator->getPassiveBonusMultiplier($selectedDominion) - 1, 4) * 100 }}%
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 table-responsive">
                                    <h4>Hero Upgrades</h4>
                                    <table class="table">
                                        <colgroup>
                                            <col width="25">
                                            <col width="25">
                                            <col width="125">
                                            <col width="25">
                                            <col width="175">
                                            <col>
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th>Name</th>
                                                <th>Level</th>
                                                <th>Combat</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        @foreach ($heroHelper->getHeroUpgradesByClass($hero->class)->where('active', true) as $upgrade)
                                            <tr class="hero-upgrade {{ $heroCalculator->canUnlockUpgrade($hero, $upgrade) ? 'text-default' : 'text-muted' }}" data-level="{{ $upgrade->type === 'directive' ? '--' : $upgrade->level }}">
                                                <td class="text-center{{ in_array($upgrade->key, $unlockedUpgrades) ? ' text-green' : null }}">
                                                    @if (in_array($upgrade->key, $unlockedUpgrades))
                                                        <i class="fa fa-check"></i>
                                                    @else
                                                        <input type="radio" name="key" id="upgrade_{{ $upgrade->key }}" value="{{ $upgrade->key }}" {{ $heroCalculator->canUnlockUpgrade($hero, $upgrade) ? null : 'disabled' }}>
                                                    @endif
                                                </td>
                                                <td>
                                                    <i class="ra ra-fw {{ $upgrade->icon }}" style="font-size: 24px";></i>
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
                                                    {!! $heroHelper->getCombatUpgradeDescription($upgrade) !!}
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
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary" {{ (!$heroCalculator->getUnlockableUpgradeCount($hero) || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Your hero gains experience and levels up, increasing its class bonuses and unlocking new upgrades.</p>
                    <p>Your hero gains 1 XP per acre gained from invasion, 0.25 XP per acre explored, 2-3 XP per successful info operation (excluding bots), 7 XP per successful black operation, and 8 XP per successful war operation.</p>
                    <p>Your hero loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.</p>
                    <p>You can change your hero class at any time, but you'll lose any progress you've made toward the next level. Any bonuses you've acquired from other classes will be halved while inactive.</p>
                    <p>Advanced hero classes have special requirements to select and unlock additional upgrades on first use. All hero upgrades are <b>permanent</b>.</p>
                    <p>There is a {{ \OpenDominion\Calculators\Dominion\HeroCalculator::CLASS_CHANGE_COOLDOWN_HOURS }} hour cooldown between class changes.</p>
                    @if ($hero && !$heroCalculator->canChangeClass($hero))
                        <p>You cannot change your hero class for another {{ $heroCalculator->hoursUntilClassChange($hero) }} hours.</p>
                    @endif
                </div>
            </div>
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

            randomizeName();
        })(jQuery);
    </script>
@endpush
