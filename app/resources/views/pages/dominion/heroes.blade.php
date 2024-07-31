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
                            <h3 class="box-title">Hero Bonuses</h3>
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
                                    $perkType = $heroHelper->getClasses()[$hero->class]['perk_type'];
                                    $bonuses = $hero->bonuses->where('type', '!=', 'status_effect')->keyBy('level');
                                    $unlockedBonuses = $hero->bonuses->pluck('key')->all();
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
                                        <div class="col-xs-6 col-sm-4 col-sm-offset-2 text-center">
                                            @if ($hero->type == 'advanced')
                                                {!! $heroHelper->getBonusIcon(0, $bonuses[0] ?? null) !!}<br/>
                                            @else
                                                <i class="hero-icon ra ra-fw {{ $heroHelper->getClassIcon($hero->class) }}" title="Class: {{ $heroHelper->getClassDisplayName($hero->class) }}" data-toggle="tooltip"></i><br/>
                                            @endif
                                            {!! $heroHelper->getBonusIcon(2, $bonuses[2] ?? null) !!}<br/>
                                            {!! $heroHelper->getBonusIcon(4, $bonuses[4] ?? null) !!}<br/>
                                        </div>
                                        <div class="col-xs-6 col-sm-4 text-center">
                                            {!! $heroHelper->getBonusIcon(6, $bonuses[6] ?? null) !!}<br/>
                                            {!! $heroHelper->getBonusIcon(8, $bonuses[8] ?? null) !!}<br/>
                                            {!! $heroHelper->getBonusIcon(10, $bonuses[10] ?? null) !!}<br/>
                                        </div>
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
                                    <h4>Hero Bonuses</h4>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Name</th>
                                                <th>Level</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        @foreach ($heroHelper->getHeroBonuses() as $bonus)
                                            <tr class="{{ $heroCalculator->canUnlockBonus($hero, $bonus) ? 'text-default' : 'text-muted' }}">
                                                <td class="text-center{{ in_array($bonus->key, $unlockedBonuses) ? ' text-green' : null }}">
                                                    @if (in_array($bonus->key, $unlockedBonuses))
                                                        <i class="fa fa-check"></i>
                                                    @else
                                                        <input type="radio" name="key" id="bonus_{{ $bonus->key }}" value="{{ $bonus->key }}" {{ $heroCalculator->canUnlockBonus($hero, $bonus) ? null : 'disabled' }}>
                                                    @endif
                                                </td>
                                                <td>
                                                    <label for="bonus_{{ $bonus->key }}" style="font-weight: normal;">
                                                        <i class="ra ra-fw {{ $bonus->icon }}"></i>
                                                        {{ $bonus->name }}
                                                    </label>
                                                </td>
                                                <td>
                                                    {{ $bonus->level ?: '--' }}
                                                </td>
                                                <td>
                                                    <label for="bonus_{{ $bonus->key }}" style="font-weight: normal;">
                                                        {!! $heroHelper->getBonusDescription($bonus, '<br/>') !!}
                                                    </label>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" class="btn btn-primary" {{ ($heroCalculator->getUnlockableBonusCount($hero) || $selectedDominion->isLocked()) ? 'disabled' : null }}>Unlock</button>
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

            randomizeName();
        })(jQuery);
    </script>
@endpush
