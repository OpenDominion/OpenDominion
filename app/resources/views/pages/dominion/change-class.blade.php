@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <form class="form-horizontal" action="{{ route('dominion.heroes.change-class', $targetClass['key']) }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Change Hero Class</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="text-center" style="font-size: 24px;">
                                    {{ $hero->name }}
                                </div>
                                <div class="text-center">
                                    Level {{ $heroCalculator->getHeroLevel($hero) }} {{ $heroHelper->getClassDisplayName($hero->class) }}
                                </div>
                                <div class="text-center">
                                    {{ $hero->experience }} / {{ $heroCalculator->getNextLevelXP($hero) }} XP
                                </div>
                                <div class="text-center">
                                    {{ $heroCalculator->getPassiveDescription($hero) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h4>Changing to {{ $targetClass['name'] }} Class</h4>
                                    <p>Your hero <strong>{{ $hero->name }}</strong> will change from <strong>{{ $heroHelper->getClassDisplayName($hero->class) }}</strong> to <strong>{{ $targetClass['name'] }}</strong>.</p>
                                    @if($targetClass['class_type'] === 'basic')
                                        <p>The hero will continue where you left off if you've played this class before, or start at 0 XP if this is a new class.</p>
                                    @else
                                        <p><strong>Warning:</strong> Advanced classes cannot be changed once selected.</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">Name</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input name="name" id="name" class="form-control" value="{{ $hero->name }}" />
                                            <div class="input-group-btn">
                                                <button id="randomize" class="btn btn-default" type="button">Randomize</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="class" value="{{ $targetClass['key'] }}" />
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <div class="pull-left">
                            <a href="{{ route('dominion.heroes') }}" class="btn btn-default">Cancel</a>
                        </div>
                        <div class="pull-right">
                            <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Change to {{ $targetClass['name'] }}</button>
                        </div>
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
                    <p>You can only have one hero at a time.</p>
                    <p>Your hero gains experience and levels up, increasing a passive bonus based on its class and unlocking new upgrades.</p>
                    <p>Your hero gains 1 XP per acre gained from invasion, 1 XP per successful info operation (excluding bots), 4 XP per successful black operation, and 6 XP per successful war operation.</p>
                    <p>Your hero loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.</p>
                    <p>Basic hero classes can be changed to another class. The hero will continue where you left off if you've played this class before, or start at 0 XP if this is a new class.</p>
                    <p>Advanced hero classes cannot be changed once selected, cannot be selected until the 5th day of the round, and unlock additional upgrades.</p>
                    <p><strong>Class Change Cooldown:</strong> There is a 48-hour cooldown between class changes to prevent frequent switching.</p>
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
