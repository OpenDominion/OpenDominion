@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <form class="form-horizontal" action="{{ route('dominion.heroes.change-class', $targetClass['key']) }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Hero Class Change</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4>{{ $hero->name }} - Level {{ $heroCalculator->getHeroLevel($hero) }} {{ $heroHelper->getClassDisplayName($hero->class) }} - {{ $hero->experience }} / {{ $heroCalculator->getNextLevelXP($hero) }} XP</h4>
                                <p>Are you sure you want to change your hero class to <strong>{{ $targetClass['name'] }}</strong>?</p>
                                <p>You will lose any progress you've made toward the next level (<b class="text-red">-{{ $hero->experience - $heroCalculator->getCurrentLevelXP($hero) }} XP</b>).</p>
                                <p>You will continue where you left off if you've used this class before, or start at 0 XP if not.</p>
                                <p>Your current <strong>{{ $heroHelper->getClassDisplayName($hero->class) }}</strong> class bonus bonus will be reduced by half while inactive.</p>
                                @if ($targetClass['key'] == 'scion')
                                    <p class="text-red">If you select <b>Scion</b>, the <b>Disarmament</b> upgrade will prevent you from attacking other dominions for the rest of the round, though you will still be able to attack wonders and raids.</p>
                                @endif
                                @if ($targetClass['key'] == 'scholar')
                                    <p class="text-red">If you select <b>Scholar</b>, the <b>Pursuit of Knowledge</b> upgrade will penalize your castle investments for the rest of the round.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                            Confirm
                        </button>
                    </div>
                </div>
            </form>

            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Heroes</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h4>Basic Classes</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Level Bonus</th>
                                    </tr>
                                </thead>
                                @foreach ($heroHelper->getBasicClasses() as $class)
                                    <tr>
                                        <td style="font-size: 24px;">
                                            <i class="ra ra-fw {{ $heroHelper->getClassIcon($class['key']) }}"></i>
                                        </td>
                                        <td>{{ $class['name'] }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $class['perk_type'])) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                        <div class="col-md-8">
                            <h4>Advanced Classes</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Level Bonus</th>
                                        <th>Requirement</th>
                                        <th>Upgrades</th>
                                    </tr>
                                </thead>
                                @foreach ($heroHelper->getAdvancedClasses() as $class)
                                    <tr>
                                        <td style="font-size: 24px;">
                                            <i class="ra ra-fw {{ $heroHelper->getClassIcon($class['key']) }}"></i>
                                        </td>
                                        <td>{{ $class['name'] }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $class['perk_type'])) }}</td>
                                        <td>{{ $heroHelper->getRequirementDisplay($class) }}</td>
                                        <td>
                                            @foreach ($heroHelper->getHeroUpgradesByName($class['perks'])->where('active', true) as $upgrade)
                                                {{ $upgrade->name }}
                                                @if ($upgrade->type == 'directive')
                                                    (required)
                                                @else
                                                    (optional)
                                                @endif
                                                <br/>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <h4>Advanced Hero Upgrades</h4>
                            <table class="table table-striped">
                                <colgroup>
                                    <col>
                                    <col width="150">
                                    <col>
                                    <col>
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Name</th>
                                        <th>Classes</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                @foreach ($heroHelper->getHeroUpgrades()->where('active', true)->where('level', 0) as $upgrade)
                                    <tr>
                                        <td style="font-size: 24px";>
                                            <i class="ra ra-fw {{ $upgrade->icon }}" title="{{ ucwords($upgrade->type) }}" data-toggle="tooltip"></i>
                                        </td>
                                        <td>{{ $upgrade->name }}</td>
                                        <td>{{ count($upgrade->classes) ? ucwords(implode(', ', $upgrade->classes)) : '--' }}</td>
                                        <td>{{ $heroHelper->getUpgradeDescription($upgrade) }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    </div>
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
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Your hero gains experience and levels up, increasing its class bonuses and unlocking new upgrades.</p>
                    <p>Your hero gains 1 XP per acre gained from invasion (against dominions at least 75% of your size), 0.6 XP per acre explored, 0.5 XP per spy strength spent on successful espionage operations (excluding theft), and 1 XP per wizard strength spent on successful magic operations.</p>
                    <p>Your hero loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.</p>
                    <p>You can change your hero class at any time, but you'll lose any progress you've made toward the next level. Any bonuses you've acquired from other classes will be halved while inactive.</p>
                    <p>Advanced hero classes have special requirements to select and unlock additional upgrades on first use. All hero upgrades are <b>permanent</b>.</p>
                    <p>There is a {{ \OpenDominion\Calculators\Dominion\HeroCalculator::CLASS_CHANGE_COOLDOWN_HOURS }} hour cooldown between class changes.</p>
                </div>
            </div>
        </div>

    </div>
@endsection
