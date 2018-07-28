@extends ('layouts.master')

@section('page-header', 'Invade')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-crossed-swords"></i> Invade</h3>
                </div>

                @if ($protectionService->isUnderProtection($selectedDominion))
                    <div class="box-body">
                        You are currently under protection for <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours and may not invade during that time.
                    </div>
                @else
                    <form action="{{ route('dominion.invade') }}" method="post" role="form">
                        @csrf

                        <div class="box-body">

                            <div class="form-group">
                                <label for="target_dominion">Select a target</label>
                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion">
                                    <option></option>
                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                        <option value="{{ $dominion->id }}" data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}" data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}">
                                            {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @foreach (range(1, 4) as $slot)
                                @php
                                    $unit = $selectedDominion->race->units->filter(function ($unit) use ($slot) {
                                        return ($unit->slot === $slot);
                                    })->first();
                                @endphp
                                @if ($unit->power_offense == 0)
                                    @continue;
                                @endif
                                <div class="form-group">
                                    <label for="unit1">{{ $unitHelper->getUnitName(('unit'.  $slot), $selectedDominion->race) }}</label>
                                    <input type="number" name="unit{{ $slot }}" class="form-control" placeholder="0 / {{ number_format($selectedDominion->{'military_unit' . $slot}) }}" min="0" max="{{ $selectedDominion->{'military_unit' . $slot} }}" data-op="{{ $unit->power_offense }}">
                                </div>
                            @endforeach

                            <label for="total_op">Total OP</label>
                            <p class="form-control-static" id="total-op">0</p>
                            
                        </div>
                    </form>

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>Invade</button>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Here you can invade other players to try to capture some of their land and to gain prestige. Invasions are successful if you send more OP than they have DP.</p>
                    <p>Find targets using <a href="{{ route('dominion.magic') }}">magic</a>,  <a href="{{ route('dominion.espionage') }}">espionage</a> and the <a href="{{ route('dominion.op-center') }}">Op Center</a>. Communicate with your realmies using the <a href="{{ route('dominion.council') }}">council</a> to coordinate attacks.</p>
                    <p>Be sure to calculate your OP vs your target's DP to avoid blindly sending your units to their doom.</p>
                    <p>You can only invade dominions that are within your range, and you will only gain prestige on targets 75% or greater relative to your own land size.</p>
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

            $('input[name^=\'unit\']').change(function (e) {
                var input = $(this);
                var allUnitInputs = $('input[name^=\'unit\']');

                var totalOP = 0;

                allUnitInputs.each(function () {
                    var amount = $(this).val();

                    if (amount === '') {
                        return true; // continue
                    }

                    totalOP += (parseInt(amount) * parseFloat($(this).data('op')));
                });

                $('#total-op').text(totalOP.toLocaleString());
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
