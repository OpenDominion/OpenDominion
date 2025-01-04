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
                                @endphp
                                <div class="col-md-6">
                                    <div class="text-center" style="font-size: 24px;">
                                        {{ $hero->name }}
                                    </div>
                                    <div class="text-center">
                                        Level {{ $heroCalculator->getHeroLevel($hero) }} {{ $heroHelper->getClassDisplayName($hero->class) }}
                                    </div>
                                    <div class="text-center">
                                        {{ floor($hero->experience) }} / {{ $heroCalculator->getNextLevelXP($hero) }} XP
                                    </div>
                                    <div class="text-center">
                                        {{ $heroCalculator->getPassiveDescription($hero) }}
                                    </div>
                                    @if ($selectedDominion->building_shrine > 0)
                                        <div class="text-center">
                                            {{ number_format($heroCalculator->getHeroPerkMultiplier($selectedDominion, $perkType) * 100, 2) }}% from Shrines
                                        </div>
                                    @endif
                                    <div class="row" style="font-size: 64px; margin-top: 20px;">
                                        @if ($heroClass['class_type'] == 'advanced')
                                            <div class="col-xs-4 text-center">
                                                <i class="hero-icon ra ra-fw {{ $heroHelper->getClassIcon($hero->class) }}" title="Class: {{ $heroHelper->getClassDisplayName($hero->class) }}" data-toggle="tooltip"></i><br/>
                                                @foreach ($upgrades[2] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(2, $upgrade) !!}<br/>
                                                @endforeach
                                                @foreach ($upgrades[4] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(4, $upgrade) !!}<br/>
                                                @endforeach
                                            </div>
                                            <div class="col-xs-4 text-center">
                                                @foreach ($upgrades[6] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(6, $upgrade) !!}<br/>
                                                @endforeach
                                                @foreach ($upgrades[8] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(8, $upgrade) !!}<br/>
                                                @endforeach
                                                @foreach ($upgrades[10] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(10, $upgrade) !!}<br/>
                                                @endforeach
                                            </div>
                                            <div class="col-xs-4 text-center">
                                                @foreach ($upgrades[0] ?? [] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(0, $upgrade) !!}<br/>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="col-xs-6 col-sm-4 col-sm-offset-2 text-center">
                                                <i class="hero-icon ra ra-fw {{ $heroHelper->getClassIcon($hero->class) }}" title="Class: {{ $heroHelper->getClassDisplayName($hero->class) }}" data-toggle="tooltip"></i><br/>
                                                @foreach ($upgrades[2] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(2, $upgrade) !!}<br/>
                                                @endforeach
                                                @foreach ($upgrades[4] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(2, $upgrade) !!}<br/>
                                                @endforeach
                                            </div>
                                            <div class="col-xs-6 col-sm-4 text-center">
                                                @foreach ($upgrades[6] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(6, $upgrade) !!}<br/>
                                                @endforeach
                                                @foreach ($upgrades[8] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(8, $upgrade) !!}<br/>
                                                @endforeach
                                                @foreach ($upgrades[10] ?? [null] as $upgrade)
                                                    {!! $heroHelper->getUpgradeIcon(10, $upgrade) !!}<br/>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 table-responsive">
                                    <table class="table table-condensed table-striped">
                                        <thead>
                                            <tr>
                                                <th>Level</th>
                                                <th>XP</th>
                                                <th>Bonus</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($heroCalculator->getExperienceLevels() as $level)
                                                @if ($level['level'] !== 0)
                                                    <tr class="{{ $heroCalculator->getHeroLevel($hero) == $level['level'] ? 'text-bold' : null }}">
                                                        <td>{{ $level['level'] }}</td>
                                                        <td>{{ $level['xp'] }}</td>
                                                        <td>{{ number_format($heroCalculator->calculatePassiveBonus($perkType, $level['level']), 2) }}%</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-12">
                                    <h4>Hero Upgrades</h4>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th></th>
                                                <th>Name</th>
                                                <th>Level</th>
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
                    <p>You can only have one hero at a time.</p>
                    <p>Your hero gains experience and levels up, increasing its class bonus and unlocking new upgrades.</p>
                    <p>Your hero gains 1 XP per acre gained from invasion, 1 XP per successful info operation (excluding bots), 4 XP per successful black operation, and 6 XP per successful war operation.</p>
                    <p>Your hero loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.</p>
                    <p>You can also <a href="{{ route('dominion.heroes.retire') }}">retire your hero</a> and create another. The new hero will start with XP equal to half that of its predecessor.</p>
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
