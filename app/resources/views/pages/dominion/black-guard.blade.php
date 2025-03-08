@extends ('layouts.master')

@section('page-header', 'Chaos League')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-burning-embers"></i> Chaos Operations</h3>
                        </div>

                        @if ($protectionService->isUnderProtection($selectedDominion))
                            <div class="box-body">
                                You are currently under protection for
                                @if ($protectionService->getUnderProtectionHoursLeft($selectedDominion))
                                    <b>{{ number_format($protectionService->getUnderProtectionHoursLeft($selectedDominion), 2) }}</b> more hours
                                @else
                                    <b>{{ $selectedDominion->protection_ticks_remaining }}</b> ticks
                                @endif
                                and may not cast any offensive spells during that time.
                            </div>
                        @elseif ($isBlackGuardMember)
                            <div class="box-body">
                                <form action="{{ route('dominion.black-guard.spell') }}" method="post" role="form">
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="target_dominion">Select a target</label>
                                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    <option></option>
                                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion, true, true) as $dominion)
                                                        @if ($guardMembershipService->isBlackGuardMember($dominion))
                                                            <option value="{{ $dominion->id }}"
                                                                    data-race="{{ $dominion->race->name }}"
                                                                    data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                                    data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}"
                                                                    data-friendly="{{ $selectedDominion->realm_id == $dominion->realm_id }}"
                                                                >
                                                                {{ $dominion->name }} (#{{ $dominion->realm->number }})
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Offensive Spells</label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        @foreach ($spellHelper->getSpells($selectedDominion->race, 'war') as $spell)
                                            @php
                                                $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                            @endphp
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                <div class="form-group">
                                                    <button type="submit"
                                                            name="spell"
                                                            value="{{ $spell->key }}"
                                                            class="btn btn-primary btn-block black-op"
                                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$canCast || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                        {{ $spellHelper->getChaosSpellName($spell) }}
                                                    </button>
                                                    <p style="margin: 5px 0;">{{ $spellHelper->getChaosSpellDescription($spell) }}</p>
                                                    <small>
                                                        Mana cost: <span class="text-{{ $canCast ? 'success' : 'danger' }}">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="row">
                                        @foreach ($spellHelper->getSpells($selectedDominion->race, 'hostile') as $spell)
                                            @php
                                                $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                            @endphp
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                <div class="form-group">
                                                    <button type="submit"
                                                            name="spell"
                                                            value="{{ $spell->key }}"
                                                            class="btn btn-primary btn-block black-op"
                                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$canCast || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                        {{ $spell->name }}
                                                    </button>
                                                    <p style="margin: 5px 0;">{{ $spellHelper->getSpellDescription($spell) }}</p>
                                                    <small>
                                                        Mana cost: <span class="text-{{ $canCast ? 'success' : 'danger' }}">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                        @if ($spell->duration)
                                                            Lasts {{ $spell->duration + 2 }} hours<br/>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Friendly Spells</label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        @foreach ($spellHelper->getSpells($selectedDominion->race, 'friendly') as $spell)
                                            @php
                                                $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                                $cooldownHours = $spellCalculator->getSpellCooldown($selectedDominion, $spell);
                                            @endphp
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                <div class="form-group">
                                                    <button type="submit"
                                                            name="spell"
                                                            value="{{ $spell->key }}"
                                                            class="btn btn-primary btn-block friendly-spell disabled"
                                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$canCast || $cooldownHours || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                        {{ $spell->name }}
                                                    </button>
                                                    <p style="margin: 5px 0;">{{ $spellHelper->getSpellDescription($spell) }}</p>
                                                    <small>
                                                        Mana cost: <span class="text-{{ $canCast ? 'success' : 'danger' }}">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                        @if ($spell->duration)
                                                            Lasts {{ $spell->duration }} hours<br/>
                                                        @endif
                                                        @if ($cooldownHours)
                                                            (<span class="text-danger">{{ $cooldownHours }} hours until recast</span>)<br/>
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </form>

                                <form action="{{ route('dominion.black-guard.espionage') }}" method="post" role="form">
                                    @csrf
                                    <input type="hidden" name="target_dominion" id="target_dominion_mirror">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Spy Operations</label>
                                        </div>
                                    </div>

                                    <div class="row">
                                        @foreach ($espionageHelper->getWarOperations() as $operation)
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                <div class="form-group">
                                                    <button type="submit"
                                                            name="operation"
                                                            value="{{ $operation['key'] }}"
                                                            class="btn btn-primary btn-block black-op"
                                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                        {{ $operation['name'] }}
                                                    </button>
                                                    <p>{{ $operation['description'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="row">
                                        @foreach ($espionageHelper->getBlackOperations() as $operation)
                                            <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                <div class="form-group">
                                                    <button type="submit"
                                                            name="operation"
                                                            value="{{ $operation['key'] }}"
                                                            class="btn btn-primary btn-block black-op"
                                                            {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$espionageCalculator->canPerform($selectedDominion, $operation['key']) || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                        {{ $operation['name'] }}
                                                    </button>
                                                    <p>{{ $operation['description'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="box-body">
                                You are not currently a member of the Chaos League.
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title text-purple"><i class="ra ra-fire-shield"></i> The Chaos League</h3>
                        </div>

                        <div class="box-body">
                            <ul class="text-left" style="padding: 0 30px;">
                                <li>Enables all war and black operations between members.</li>
                                <li>War spells between members are now CHAOS spells.</li>
                                <ul>
                                    <li>Chaos Fireball - kills 7.5% peasants.</li>
                                    <li>Chaos Lightning - temporarily reduces castle improvements by  0.3%.</li>
                                    <li>Chaos Disband - turns 2% of enemy spies into random resources for yourself.</li>
                                    <li>Chance for critical success, dealing 50% more damage and increasing chance of critical failure.</li>
                                    <li>Chance for critical failure, dealing damage to yourself.</li>
                                    <li>Chance of critical success decreases and chance of critical failure increases based on the number of other members in your realm.</li>
                                </ul>
                                <li>Gain access to self spell: Delve into Shadow (cannot be used in guard).</li>
                                <ul>
                                    <li>Failed CHAOS spells refund 40% of their strength and mana costs.</li>
                                    <li>Reduces exploration cost based on your wizard mastery.</li>
                                </ul>
                                <li>Gain access to friendly spells to use on other members in your realm.</li>
                                <li>75% of casualties suffered due to failed operations between members are automatically re-trained.</li>
                                <li>Info op strength costs are halved (even against non-members).</li>
                            </ul>
                            @if ($isLeavingBlackGuard)
                                <form action="{{ route('dominion.government.black-guard.cancel') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" class="btn btn-warning btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                        Remain in Chaos League
                                    </button>
                                </form>
                            @elseif ($isBlackGuardApplicant || $isBlackGuardMember)
                                <form action="{{ route('dominion.government.black-guard.leave') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm-lg" {{ $selectedDominion->isLocked() || $hoursBeforeLeaveBlackGuard ? 'disabled' : null }}>
                                        @if ($isBlackGuardMember)
                                            Leave Chaos League
                                        @else
                                            Cancel Application
                                        @endif
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dominion.government.black-guard.join') }}" method="post" role="form">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm-lg" {{ $selectedDominion->isLocked() || !$canJoinGuards ? 'disabled' : null }}>
                                        Request to Join Chaos League
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>You can perform all war and black operations against other members of the Chaos League.</p>
                    <p>War spells are empowered in the Chaos League, changing their effects. They also have a 25% chance of critical success, which deals 50% more damage. They also have a chance of critical failure, which deals damage to yourself.</p>
                    <p>War and black ops cannot be performed until the 4th day of the round.<p>
                    <p>You have {{ number_format($selectedDominion->resource_mana) }} <b>mana</b> and {{ sprintf("%.4g", $selectedDominion->wizard_strength) }}% <b>wizard strength</b>.</p>
                    <p>You have {{ sprintf("%.4g", $selectedDominion->spy_strength) }}% <b>spy strength</b>.</p>
                    <p>Joining the Chaos League takes 12 hours and you cannot leave for the first 12 hours after joining. Leaving the Chaos League also requires an additional 12 hours to go into effect.</p>

                    @if ($isBlackGuardMember)
                        <p>You are a member of the <span class="text-purple"><i class="ra ra-fire-shield" title="Chaos League"></i>Chaos League</span>.</p>
                        @if ($hoursBeforeLeaveBlackGuard)
                            <p class="text-red">You cannot leave for {{ $hoursBeforeLeaveBlackGuard }} hours.</p>
                        @endif
                        @if ($isLeavingBlackGuard)
                            <p>You will leave the Chaos League in {{ $hoursBeforeLeavingBlackGuard }} hours.</p>
                        @endif
                    @elseif ($isBlackGuardApplicant)
                        <p>You will become a member of the Chaos League in {{ $hoursBeforeBlackGuardMember }} hours.</p>
                    @endif
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
                var friendlyStatus = $(this).find(":selected").data('friendly');
                if (friendlyStatus) {
                    $('.friendly-spell').removeClass('disabled');
                    $('.black-op').addClass('disabled');
                } else {
                    $('.friendly-spell').addClass('disabled');
                    $('.black-op').removeClass('disabled');
                }
                $('#target_dominion_mirror').val($(this).val());
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
            const friendly = state.element.dataset.friendly;
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
            if (friendly == 1) {
                warStatus = '<div class="pull-left">&nbsp;|&nbsp;<span class="text-green">FRIENDLY</span></div>';
            } else {
                warStatus = '<div class="pull-left">&nbsp;|&nbsp;<span class="text-red">HOSTILE</span></div>';
            }

            return $(`
                <div class="pull-left">${state.text.replace(/\</g,"&lt;")} - ${race}</div>
                ${warStatus}
                <div class="pull-right">${land} land <span class="${difficultyClass}">(${percentage}%)</span></div>
                <div style="clear: both;"></div>
            `);
        }
    </script>
@endpush
