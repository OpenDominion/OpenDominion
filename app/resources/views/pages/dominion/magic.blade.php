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
                                @foreach ($spellHelper->getSelfSpells($selectedDominion->race)->chunk(2) as $spells)
                                    <div class="row">
                                        @foreach ($spells as $spell)
                                            <div class="col-xs-6 col-sm-6 col-md-12 col-lg-6 text-center">
                                                @php
                                                    $canCast = $spellCalculator->canCast($selectedDominion, $spell['key']);
                                                    $isActive = $spellCalculator->isSpellActive($selectedDominion, $spell['key']);

                                                    $buttonStyle = ($isActive ? 'btn-success' : 'btn-primary');
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
                                                                Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell['key'])) }}</span>
                                                            @else
                                                                Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell['key'])) }}</span>
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

                        @if ($protectionService->isUnderProtection($selectedDominion))
                            <div class="box-body">
                                You are currently under protection for <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours and may not cast any offensive spells during that time.
                            </div>
                        @else
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf

                                <div class="box-body">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="target_dominion">Select a target</label>
                                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    <option></option>
                                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                                        <option value="{{ $dominion->id }}" data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}" data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}">
                                                            {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Information Gathering Spells</label>
                                        </div>
                                    </div>

                                    @foreach ($spellHelper->getInfoOpSpells()->chunk(4) as $spells)
                                        <div class="row">
                                            @foreach ($spells as $spell)
                                                <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                    <div class="form-group">
                                                        <button type="submit" name="spell" value="{{ $spell['key'] }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$spellCalculator->canCast($selectedDominion, $spell['key']) ? 'disabled' : null }}>
                                                            {{ $spell['name'] }}
                                                        </button>
                                                        <p>{{ $spell['description'] }}</p>
                                                        <small>
                                                            @if ($canCast)
                                                                Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell['key'])) }}</span>
                                                            @else
                                                                Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell['key'])) }}</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                </div>
                            </form>
                        @endif

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
                    <p>Here you may cast spells which temporarily benefit your dominion or hinder opposing dominions. You can also perform information gathering operations with magic.</p>
                    <p>Non-information gathering spells last for <b>12 hours</b>, unless stated otherwise.</p>
                    <p>Any obtained data after successfully casting an information gathering spell gets posted to the <a href="{{ route('dominion.op-center') }}">Op Center</a> for your realmies.</p>
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
                <div class="pull-right">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
