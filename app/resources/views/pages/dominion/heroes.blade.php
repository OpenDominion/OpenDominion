@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            @if ($heroes->isEmpty())
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
                                    <div class="form-group hidden">
                                        <label class="col-sm-3 control-label">Class</label>
                                        <div class="col-sm-9">
                                            <select name="class" class="form-control">
                                                @foreach ($heroHelper->getClasses() as $class)
                                                    <option value="{{ $class['key'] }}">
                                                        {{ $class['name'] }} - Gains double XP from {{ $class['xp_bonus_type'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Class</label>
                                        <div class="col-sm-9">
                                            <select name="trade" class="form-control">
                                                @foreach ($heroHelper->getTrades() as $trade)
                                                    <option value="{{ $trade['key'] }}">
                                                        {{ $trade['name'] }} - {{ str_replace('_', ' ', $trade['perk_type']) }}
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
                                        @foreach ($heroHelper->getTrades() as $trade)
                                            <th>{{ $trade['name'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($heroCalculator->getExperienceLevels() as $level)
                                        @if ($level['level'] !== 0)
                                            <tr>
                                                <td>{{ $level['level'] }}</td>
                                                <td>{{ $level['xp'] }}</td>
                                                @foreach ($heroHelper->getTrades() as $trade)
                                                    <th>{{ number_format($heroCalculator->calculateTradeBonus($trade['perk_type'], $level['level']), 2) }}%</th>
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
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Heroes</h3>
                    </div>
                    <div class="box-body">
                        @foreach ($heroes as $hero)
                            @php $perkType = $heroHelper->getTrades()[$hero->trade]['perk_type']; @endphp
                            <div class="row">
                                <div class="col-md-6">
                                    <!--
                                    <div class="col-md-6">
                                        <div class="row" style="font-size: 36px;">
                                            <div class="col-xs-3">
                                                <i class="ra ra-knight-helmet" title="Helmet" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-sword" title="Sword" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-shield" title="Shield" data-toggle="tooltip"></i>
                                            </div>
                                            <div class="col-xs-6">
                                                <img class="img-responsive" src="https://place-hold.it/200x300" />
                                            </div>
                                            <div class="col-xs-3">
                                                <i class="ra ra-gold-bar" title="Alchemist" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-falling" title="Ooopsie" data-toggle="tooltip"></i><br/>
                                                <i class="ra ra-roast-chicken" title="Hangry" data-toggle="tooltip"></i>
                                            </div>
                                        </div>
                                    </div>
                                    -->
                                    <div class="text-center" style="font-size: 24px;">
                                        {{ $hero->name }}
                                    </div>
                                    <div class="text-center">
                                        Level {{ $heroCalculator->getHeroLevel($hero) }} {{ $heroHelper->getTradeDisplayName($hero->trade) }}
                                    </div>
                                    <div class="text-center">
                                        {{ floor($hero->experience) }} / {{ $heroCalculator->getNextLevelXP($hero) }} XP
                                    </div>
                                    <div class="text-center">
                                        {{ $heroCalculator->getTradeDescription($hero) }}
                                    </div>
                                    @if ($selectedDominion->building_shrine > 0)
                                        <div class="text-center">
                                            {{ number_format($heroCalculator->getHeroPerkMultiplier($selectedDominion, $perkType) * 100, 2) }}% from Shrines
                                        </div>
                                    @endif
                                    <div class="text-center" style="font-size: 64px;">
                                        <i class="{{ $heroHelper->getTradeIconClass($hero->trade) }}" title="{{ $heroHelper->getTradeDisplayName($hero->trade) }}" data-toggle="tooltip" data-placement="bottom"></i>
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
                                                        <td>{{ number_format($heroCalculator->calculateTradeBonus($perkType, $level['level']), 2) }}%</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
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
                    <p>Your hero gains 1 XP per acre gained from invasion, 2 XP per successful info operation (excluding bots), 4 XP per successful black operation, and 6 XP per successful war operation.</p>
                    <p>Your hero loses 1 XP per acre lost from invasion, however this loss cannot exceed the XP required to maintain its current level.</p>
                    <p>You can also <a href="{{ route('dominion.heroes.retire') }}">retire your hero</a> and create another. The new hero will start with XP equal to half that of its predecessor.</p>
                    <!--
                    <table class="table table-condensed">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>XP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($heroCalculator->getExperienceLevels() as $level)
                                @if ($level['level'] !== 0)
                                    <tr>
                                        <td>{{ $level['level'] }}</td>
                                        <td>{{ $level['xp'] }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                    -->
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
