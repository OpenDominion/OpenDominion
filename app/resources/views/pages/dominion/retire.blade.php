@extends('layouts.master')

@section('page-header', 'Heroes')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <form class="form-horizontal" action="{{ route('dominion.heroes.retire') }}" method="post" role="form">
                @csrf
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="ra ra-knight-helmet"></i> Heroes</h3>
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
                                            @foreach ($heroHelper->getClasses() as $class)
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
                        <div class="pull-right">
                            <button type="submit" class="btn btn-danger" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Retire Hero</button>
                        </div>
                    </div>
                </div>
            </form>
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
