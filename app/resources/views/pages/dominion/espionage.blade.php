@extends ('layouts.master')

@section('page-header', 'Espionage')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-user-secret"></i> Offensive Operations</h3>
                </div>

                @if ($protectionService->isUnderProtection($selectedDominion))
                    <div class="box-body">
                        You are currently under protection for <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours and may not perform any espionage operations during that time.
                    </div>
                @else
                    <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                        @csrf

                        <div class="box-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="target_dominion">Select a target</label>
                                        <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            <option></option>
                                            @foreach ($rangeCalculator->getDominionsInRange($selectedDominion) as $dominion)
                                                <option value="{{ $dominion->id }}"
                                                        data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                        data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 1) }}"
                                                        data-war="{{ ($selectedDominion->realm->war_realm_id == $dominion->realm->id || $dominion->realm->war_realm_id == $selectedDominion->realm->id || in_array($dominion->id, $militaryCalculator->getRecentlyInvadedBy($selectedDominion, 12))) ? 1 : 0 }}">
                                                    {{ $dominion->name }} (#{{ $dominion->realm->number }}) - {{ $dominion->race->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <label>Information Gathering Operations</label>
                                </div>
                            </div>

                            @foreach ($espionageHelper->getInfoGatheringOperations()->chunk(4) as $operations)
                                <div class="row">
                                    @foreach ($operations as $operation)
                                        <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit" name="operation" value="{{ $operation['key'] }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) ? 'disabled' : null }}>
                                                    {{ $operation['name'] }}
                                                </button>
                                                <p>{{ $operation['description'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="row">
                                <div class="col-md-12">
                                    <label>Resource Theft Operations</label>
                                </div>
                            </div>

                            @foreach ($espionageHelper->getResourceTheftOperations()->chunk(4) as $operations)
                                <div class="row">
                                    @foreach ($operations as $operation)
                                        <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit"
                                                        name="operation"
                                                        value="{{ $operation['key'] }}"
                                                        class="btn btn-primary btn-block"
                                                        {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 7) ? 'disabled' : null }}>
                                                    {{ $operation['name'] }}
                                                </button>
                                                <p>{{ $operation['description'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="row">
                                <div class="col-md-12">
                                    <label>Black Operations</label>
                                </div>
                            </div>

                            @foreach ($espionageHelper->getBlackOperations()->chunk(4) as $operations)
                                <div class="row">
                                    @foreach ($operations as $operation)
                                        <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit"
                                                        name="operation"
                                                        value="{{ $operation['key'] }}"
                                                        class="btn btn-primary btn-block"
                                                        {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 7) ? 'disabled' : null }}>
                                                    {{ $operation['name'] }}
                                                </button>
                                                <p>{{ $operation['description'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach

                            <div class="row">
                                <div class="col-md-12">
                                    <label>War Operations</label>
                                </div>
                            </div>

                            @foreach ($espionageHelper->getWarOperations()->chunk(4) as $operations)
                                <div class="row">
                                    @foreach ($operations as $operation)
                                        <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit"
                                                        name="operation"
                                                        value="{{ $operation['key'] }}"
                                                        class="btn btn-primary btn-block war-op disabled"
                                                        {{ $selectedDominion->isLocked() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 7) ? 'disabled' : null }}>
                                                    {{ $operation['name'] }}
                                                </button>
                                                <p>{{ $operation['description'] }}</p>
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

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>Here you can perform espionage operations on hostile dominions to win important information for you and your realmies.</p>
                    <p>Any obtained data after successfully performing an information gathering operation gets posted to the <a href="{{ route('dominion.op-center') }}">Op Center</a> for your realmies.</p>
                    <p>Theft can only be performed on dominions greater than your size. Theft and black ops cannot be performed until the 8th day of the round.</p>
                    <p>Performing espionage operations spends some spy strength, but it regenerates a bit every hour. You may only perform espionage operations above 30% strength.</p>
                    <p>You have {{ floor($selectedDominion->spy_strength) }}% spy strength.</p>
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
            $('#target_dominion').select2({
                templateResult: select2Template,
                templateSelection: select2Template,
            });
            $('#target_dominion').change(function(e) {
                var warStatus = $(this).find(":selected").data('war');
                if (warStatus == 1) {
                    $('.war-op').removeClass('disabled');
                } else {
                    $('.war-op').addClass('disabled');
                }
            });
            @if (session('target_dominion'))
                $('#target_dominion').val('{{ session('target_dominion') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            const war = state.element.dataset.war;
            let difficultyClass;

            if (percentage >= 120) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 66) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;<span class="text-red">WAR</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text}</div>
                ${warStatus}
                <div class="pull-right">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
