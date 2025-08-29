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
                    <p>Each dominion can select one hero that gains experience and levels up, increasing a passive bonus based on its class and unlocking new upgrades.</p>
                    <p>
                        Heroes gain 1 XP per acre gained from invasion, 0.25 XP per acre explored, 1-2 XP per successful info operation (excluding bots), 4 XP per successful black operation, and 6 XP per successful war operation.
                        Heroes lose 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.
                    </p>
                    <p>You can change your hero class at any time. Any bonuses you've earned on other classes will be halved while inactive.</p>
                    <p>Advanced hero classes unlock additional upgrades. All hero upgrades are <b>permanent</b>.</p>
                    <p>There is a 48 hour cooldown between class changes.</p>
                </div>
                <div class="col-md-4">
                    <h4>Basic Classes</h4>
                    <div class="table-responsive">
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
                </div>
                <div class="col-md-8 table-responsive">
                    <h4>Advanced Classes</h4>
                    <div class="table-responsive">
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
                    </div>

                    <h4>Hero Upgrades</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <colgroup>
                                <col>
                                <col width="150">
                                <col>
                                <col>
                                <col>
                            </colgroup>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Level</th>
                                    <th>Classes</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            @foreach ($heroHelper->getHeroUpgrades()->where('active', true) as $upgrade)
                                <tr>
                                    <td style="font-size: 24px";>
                                        <i class="ra ra-fw {{ $upgrade->icon }}" title="{{ ucwords($upgrade->type) }}" data-toggle="tooltip"></i>
                                    </td>
                                    <td>{{ $upgrade->name }}</td>
                                    <td>{{ $upgrade->type === 'directive' ? '--' : $upgrade->level }}</td>
                                    <td>{{ count($upgrade->classes) ? ucwords(implode(', ', $upgrade->classes)) : '--' }}</td>
                                    <td>{{ $heroHelper->getUpgradeDescription($upgrade) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
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
