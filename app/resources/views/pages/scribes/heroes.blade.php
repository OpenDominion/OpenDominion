@extends('layouts.topnav')

@section('content')
    @include('partials.scribes.nav')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Heroes</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-12">
                    <p>Each dominion can select one hero that gains experience and levels up, increasing a passive bonus based on its class.</p>
                    <p>
                        Heroes gains 1 XP per acre gained from invasion, 2 XP per successful info operation (excluding bots), 4 XP per successful black operation, and 6 XP per successful war operation.
                        Heroes loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.
                    </p>
                    <p>Basic hero classes can be retired and replaced with another class. Basic hero classes will start with XP equal to half that of its predecessor.</p>
                    <p>Advanced hero classes cannot be retired, cannot be selected until the 10th day of the round, and unlock additional upgrades.</p>
                </div>
                <div class="col-md-4">
                    <h4>Basic Classes</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Level Bonus</th>
                            </tr>
                        </thead>
                        @foreach ($heroHelper->getBasicClasses() as $class)
                            <tr>
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
                                <th>Name</th>
                                <th>Level Bonus</th>
                                <th>Requirement</th>
                                <th>Starting XP</th>
                                <th>Upgrades</th>
                            </tr>
                        </thead>
                        @foreach ($heroHelper->getAdvancedClasses() as $class)
                            <tr>
                                <td>{{ $class['name'] }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $class['perk_type'])) }}</td>
                                <td>{{ $heroHelper->getRequirementDisplay($class) }}</td>
                                <td>{{ $heroHelper->getStartingExperienceDisplay($class) }}</td>
                                <td>
                                    @foreach ($heroHelper->getHeroUpgradesByName($class['perks']) as $upgrade)
                                        {{ $upgrade->name }}
                                        @if ($upgrade->type !== 'directive')
                                            (optional)
                                        @endif
                                        <br/>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </table>

                    <h4>Hero Upgrades</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Level</th>
                                <th>Classes</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        @foreach ($heroHelper->getHeroUpgrades() as $upgrade)
                            <tr>
                                <td>{{ $upgrade->name }}</td>
                                <td>{{ $upgrade->type === 'directive' ? '--' : $upgrade->level }}</td>
                                <td>{{ count($upgrade->classes) ? ucwords(implode(', ', $upgrade->classes)) : '--' }}</td>
                                <td>{{ $heroHelper->getUpgradeDescription($upgrade) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            <em>
                <p>More information can be found on the <a href="https://wiki.opendominion.net/wiki/Heroes">wiki</a>.</p>
            </em>
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
@endsection
