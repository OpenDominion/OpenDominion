@extends ('layouts.master')

@section('page-header', 'Espionage')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-user-secret"></i> Offensive Operations</h3>
                </div>

                @if ($protectionService->isUnderProtection($selectedDominion))
                    <div class="card-body">
                        You are currently under protection for
                        @if ($protectionService->getUnderProtectionHoursLeft($selectedDominion))
                            <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours
                        @else
                            <b>{{ $selectedDominion->protection_ticks_remaining }}</b> ticks
                        @endif
                        and may not perform any espionage operations during that time.
                    </div>
                @else
                    <form action="{{ route('dominion.espionage') }}" method="post" role="form">
                        @csrf

                        @php
                            $recentlyInvadedByDominionIds = $militaryCalculator->getRecentlyInvadedBy($selectedDominion, 12);
                        @endphp

                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="target_dominion">Select a target</label>
                                        <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                            <option></option>
                                            @foreach ($rangeCalculator->getDominionsInRange($selectedDominion, true) as $dominion)
                                                <option value="{{ $dominion->id }}"
                                                        data-race="{{ $dominion->race->name }}"
                                                        data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                        data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}"
                                                        data-war="{{ $governmentService->isAtWar($selectedDominion->realm, $dominion->realm) ? 1 : 0 }}"
                                                        data-revenge="{{ in_array($dominion->id, $recentlyInvadedByDominionIds) ? 1 : 0 }}"
                                                        data-guard="{{ $guardMembershipService->isBlackGuardMember($dominion) && $guardMembershipService->isBlackGuardMember($selectedDominion) ? 1 : 0 }}"
                                                    >
                                                    {{ $dominion->name }} (#{{ $dominion->realm->number }})
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
                                        <div class="col-6 col-sm-3 col-md-6 col-lg-3 text-center">
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
                                        <div class="col-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit"
                                                        name="operation"
                                                        value="{{ $operation['key'] }}"
                                                        class="btn btn-primary btn-block"
                                                        {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
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
                                        <div class="col-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit"
                                                        name="operation"
                                                        value="{{ $operation['key'] }}"
                                                        class="btn btn-primary btn-block"
                                                        {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
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
                                        <div class="col-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                            <div class="form-group">
                                                <button type="submit"
                                                        name="operation"
                                                        value="{{ $operation['key'] }}"
                                                        class="btn btn-primary btn-block war-op disabled"
                                                        {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
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
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Information</h3>
                </div>
                <div class="card-body">
                    <p>Here you can perform espionage operations on hostile dominions to gain important information for you and your realmies.</p>
                    <p>Any obtained data after successfully performing an information gathering operation gets posted to the <a href="{{ route('dominion.op-center') }}">Op Center</a> for your realmies.</p>
                    <p>Theft can only be performed on dominions greater than your size. Theft and black ops cannot be performed until the 4th day of the round.</p>
                    <p>Performing espionage operations spends some spy strength (2% for info, otherwise 5%), but it regenerates 4% every hour. You may only perform espionage operations at or above 30% strength.</p>
                    <p>You have {{ sprintf("%.4g", $selectedDominion->spy_strength) }}% spy strength.</p>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-styles')
@endpush

@push('page-scripts')
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
                var revengeStatus = $(this).find(":selected").data('revenge');
                var guardStatus = $(this).find(":selected").data('guard');
                if (warStatus == 1 || revengeStatus == 1 || guardStatus == 1) {
                    $('.war-op').removeClass('disabled');
                } else {
                    $('.war-op').addClass('disabled');
                }
            });
            @if ($targetDominion)
                $('#target_dominion').val('{{ $targetDominion }}').trigger('change.select2').trigger('change');
            @endif
            @if (session('target_dominion'))
                $('#target_dominion').val('{{ session('target_dominion') }}').trigger('change.select2').trigger('change');
            @endif
        })(jQuery);

        function select2Template(state) {
            if (!state.id) {
                return state.text;
            }

            const race = state.element.dataset.race;
            const land = state.element.dataset.land;
            const percentage = state.element.dataset.percentage;
            const war = state.element.dataset.war;
            const revenge = state.element.dataset.revenge;
            const guard = state.element.dataset.guard;
            let difficultyClass;

            if (percentage >= 133) {
                difficultyClass = 'text-red';
            } else if (percentage >= 75) {
                difficultyClass = 'text-green';
            } else if (percentage >= 60) {
                difficultyClass = 'text-muted';
            } else {
                difficultyClass = 'text-gray';
            }

            warStatus = '';
            if (war == 1) {
                warStatus = '<div class="float-start">&nbsp;|&nbsp;<span class="text-red">WAR</span></div>';
            } else if (guard == 1) {
                warStatus = '<div class="float-start">&nbsp;|&nbsp;<span class="text-red">SHADOW LEAGUE</span></div>';
            } else if (revenge == 1) {
                warStatus = '<div class="float-start">&nbsp;|&nbsp;<span class="text-red">REVENGE</span></div>';
            }

            return $(`
                <div class="float-start">${state.text.replace(/\</g,"&lt;")} - ${race}</div>
                ${warStatus}
                <div class="float-end">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
