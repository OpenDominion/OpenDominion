@extends ('layouts.master')

@section('page-header', 'Magic')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="row">

                <div class="col-md-4">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-fairy-wand"></i> Self Spells</h3>
                        </div>
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf

                            <div class="box-body">
                                @foreach ($spellHelper->getSelfSpells()->chunk(2) as $spells)
                                    <div class="row">
                                        @foreach ($spells as $spell)
                                            <div class="col-xs-6 col-sm-6 col-md-12 col-lg-6 text-center">
                                                @php
                                                    $manaCost = ($spell['mana_cost'] * $landCalculator->getTotalLand($selectedDominion));
                                                    $canCast = (($selectedDominion->resource_mana >= $manaCost) && ($selectedDominion->wizard_strength >= 30));

                                                    $isActive = $spellCalculator->isSpellActive($selectedDominion, $spell['key']);

                                                    if ($isActive) {
                                                        $buttonStyle = 'btn-success';
                                                    } elseif ($canCast) {
                                                        $buttonStyle = 'btn-primary';
                                                    } else {
                                                        $buttonStyle = 'btn-danger';
                                                    }
                                                @endphp
                                                <div class="form-group">
                                                    <button type="submit" name="spell" value="{{ $spell['key'] }}" class="btn {{ $buttonStyle }} btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                        {{ $spell['name'] }}
                                                    </button>
                                                    <p>{{ $spell['description'] }}</p>
                                                    <small>
                                                        @if ($isActive)
                                                            ({{ $spellCalculator->getSpellDuration($selectedDominion, $spell['key']) }} hours remaining)
                                                        @else
                                                            @if ($canCast)
                                                                Mana cost: <span class="text-success">{{ number_format($manaCost) }}</span>
                                                            @else
                                                                Mana cost: <span class="text-danger">{{ number_format($manaCost) }}</span>
                                                            @endif
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-burning-embers"></i> Offensive Spells</h3>
                        </div>
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf

                            <div class="box-body">

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="target_dominion">Target</label>
                                            <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion">
                                                <option></option>
                                                @php
                                                    $selfLand = $landCalculator->getTotalLand($selectedDominion);
                                                @endphp
                                                @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                                    @php
                                                        $land = $landCalculator->getTotalLand($dominion);
                                                        $percentage = (($land / $selfLand) * 100);
                                                    @endphp
                                                    <option value="{{ $dominion->id }}" data-land="{{ $land }}" data-percentage="{{ number_format($percentage, 1) }}">
                                                        {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                @foreach ($spellHelper->getOffensiveSpells()->chunk(4) as $spells)
                                    <div class="row">
                                        @foreach ($spells as $spell)
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                @php
                                                    $manaCost = ($spell['mana_cost'] * $landCalculator->getTotalLand($selectedDominion));
                                                    $canCast = (($selectedDominion->resource_mana >= $manaCost) && ($selectedDominion->wizard_strength >= 30));
                                                @endphp
                                                <div class="form-group">
                                                    <button type="submit" name="spell" value="{{ $spell['key'] }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                        {{ $spell['name'] }}
                                                    </button>
                                                    <p>{{ $spell['description'] }}</p>
                                                    <small>
                                                        @if ($canCast)
                                                            Mana cost: <span class="text-success">{{ number_format($manaCost) }}</span>
                                                        @else
                                                            Mana cost: <span class="text-danger">{{ number_format($manaCost) }}</span>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach

                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                    <a href="{{ route('dominion.advisors.magic') }}" class="pull-right">Magic Advisor</a>
                </div>
                <div class="box-body">
                    <p>Here you may cast spells which temporarily benefit your dominion or hinder opposing dominions.</p>
                    <p>Casting spells spends some wizard strength, but it regenerates a bit every hour. You may only cast spells above 30% strength.</p>
                    <p>You have {{ number_format($selectedDominion->resource_mana) }} mana and {{ floor($selectedDominion->wizard_strength) }}% wizard strength.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/select2/css/select2.min.css') }}">
@endpush

@push('page-scripts')
    <script type="text/javascript" src="{{ asset('assets/vendor/select2/js/select2.full.min.js') }}"></script>
@endpush

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            $('.select2').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            let difficultyClass;

            if (percentage >= 133) {
                difficultyClass = 'text-red';
            } else if (percentage >= 120) {
                difficultyClass = 'text-orange';
            } else if (percentage >= 75) {
                difficultyClass = 'text-yellow';
            } else if (percentage >= 66) {
                difficultyClass = 'text-green';
            } else {
                difficultyClass = 'text-muted';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                <div class="pull-right">${land} land, <span class="${difficultyClass}">${percentage}%</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
