@extends ('layouts.master')

@section('page-header', 'Magic')

@section('content')
    <div class="row">

        <div class="col-sm-12 col-md-9">
            <div class="row">

                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-burning-embers"></i> Offensive Spells</h3>
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
                        @else
                            <form action="{{ route('dominion.magic') }}" method="post" role="form">
                                @csrf

                                @php
                                    $recentlyInvadedByDominionIds = $militaryCalculator->getRecentlyInvadedBy($selectedDominion, 12);
                                @endphp

                                <div class="box-body">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="target_dominion">Select a target</label>
                                                <select name="target_dominion" id="target_dominion" class="form-control select2" required style="width: 100%" data-placeholder="Select a target dominion" {{ $selectedDominion->isLocked() ? 'disabled' : null }}>
                                                    <option></option>
                                                    @php
                                                        $courtMember = ($selectedDominion->isMagister() || $selectedDominion->isMage());
                                                        $isBlackGuard = $guardMembershipService->isBlackGuardMember($selectedDominion);
                                                        $includeFriendly = ($courtMember || $isBlackGuard);
                                                    @endphp
                                                    @foreach ($rangeCalculator->getDominionsInRange($selectedDominion, true, $includeFriendly) as $dominion)
                                                        @if ($selectedDominion->realm_id !== $dominion->realm_id || $courtMember || ($isBlackGuard && $guardMembershipService->isBlackGuardMember($dominion)))
                                                            <option value="{{ $dominion->id }}"
                                                                    data-race="{{ $dominion->race->name }}"
                                                                    data-land="{{ number_format($landCalculator->getTotalLand($dominion)) }}"
                                                                    data-percentage="{{ number_format($rangeCalculator->getDominionRange($selectedDominion, $dominion), 2) }}"
                                                                    data-war="{{ $governmentService->isAtWar($selectedDominion->realm, $dominion->realm) ? 1 : 0 }}"
                                                                    data-revenge="{{ in_array($dominion->id, $recentlyInvadedByDominionIds) ? 1 : 0 }}"
                                                                    data-guard="{{ $guardMembershipService->isBlackGuardMember($dominion) && $guardMembershipService->isBlackGuardMember($selectedDominion) ? 1 : 0 }}"
                                                                    data-friendly="{{ $includeFriendly && ($selectedDominion->realm_id == $dominion->realm_id) }}"
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
                                            <label>Information Gathering Spells</label>
                                        </div>
                                    </div>

                                    @foreach ($spellHelper->getSpells($selectedDominion->race, 'info')->chunk(4) as $spells)
                                        <div class="row">
                                            @foreach ($spells as $spell)
                                                @php
                                                    $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                                @endphp
                                                <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                    <div class="form-group">
                                                        <button type="submit" name="spell" value="{{ $spell->key }}" class="btn btn-primary btn-block" {{ $selectedDominion->isLocked() || !$canCast ? 'disabled' : null }}>
                                                            {{ $spell->name }}
                                                        </button>
                                                        <p style="margin: 5px 0;">{{ $spellHelper->getSpellDescription($spell) }}</p>
                                                        <small>
                                                            @if ($canCast)
                                                                Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span>
                                                            @else
                                                                Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Black Op Spells</label>
                                        </div>
                                    </div>

                                    @foreach ($spellHelper->getSpells($selectedDominion->race, 'hostile')->chunk(4) as $spells)
                                        <div class="row">
                                            @foreach ($spells as $spell)
                                                @php
                                                    $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                                @endphp
                                                <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                    <div class="form-group">
                                                        <button type="submit"
                                                                name="spell"
                                                                value="{{ $spell->key }}"
                                                                class="btn btn-primary btn-block hostile-spell"
                                                                {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$canCast || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                            {{ $spell->name }}
                                                        </button>
                                                        <p style="margin: 5px 0;">{{ $spellHelper->getSpellDescription($spell) }}</p>
                                                        <small>
                                                            @if ($canCast)
                                                                Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span>
                                                            @else
                                                                Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>War Spells</label>
                                        </div>
                                    </div>

                                    @foreach ($spellHelper->getSpells($selectedDominion->race, 'war')->chunk(4) as $spells)
                                        <div class="row">
                                            @foreach ($spells as $spell)
                                                @php
                                                    $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                                @endphp
                                                <div class="col-xs-6 col-sm-3 col-md-6 col-lg-3 text-center">
                                                    <div class="form-group war-non-chaos">
                                                        <button type="submit"
                                                                name="spell"
                                                                value="{{ $spell->key }}"
                                                                class="btn btn-primary btn-block war-spell disabled"
                                                                {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$canCast || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                            {{ $spell->name }}
                                                        </button>
                                                        <p style="margin: 5px 0;">{{ $spellHelper->getSpellDescription($spell) }}</p>
                                                        <small>
                                                            @if ($canCast)
                                                                Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                            @else
                                                                Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                            @endif
                                                            @if ($spell->duration)
                                                                Lasts {{ $spell->duration }} hours<br/>
                                                            @endif
                                                            @if (!empty($spell->races))
                                                                Racial<br/>
                                                            @endif
                                                        </small>
                                                    </div>
                                                    @if ($isBlackGuard)
                                                        <div class="form-group war-chaos" style="display: none;">
                                                            <button type="submit"
                                                                    name="spell"
                                                                    value="{{ $spell->key }}"
                                                                    class="btn btn-primary btn-block war-spell disabled"
                                                                    {{ $selectedDominion->isLocked() || $selectedDominion->round->hasOffensiveActionsDisabled() || !$canCast || (now()->diffInDays($selectedDominion->round->start_date) < 3) ? 'disabled' : null }}>
                                                                {{ $spellHelper->getChaosSpellName($spell) }}
                                                            </button>
                                                            <p style="margin: 5px 0;">{{ $spellHelper->getChaosSpellDescription($spell) }}</p>
                                                            <small>
                                                                @if ($canCast)
                                                                    Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                                @else
                                                                    Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                                @endif
                                                                @if ($spell->duration)
                                                                    Lasts {{ $spell->duration }} hours<br/>
                                                                @endif
                                                                @if (!empty($spell->races))
                                                                    Racial<br/>
                                                                @endif
                                                            </small>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Friendly Spells</label>
                                        </div>
                                    </div>

                                    @foreach ($spellHelper->getSpells($selectedDominion->race, 'friendly')->chunk(4) as $spells)
                                        <div class="row">
                                            @foreach ($spells as $spell)
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
                                    @endforeach

                                </div>
                            </form>
                        @endif

                    </div>
                </div>

                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="ra ra-fairy-wand"></i> Self Spells</h3>
                        </div>
                        <form action="{{ route('dominion.magic') }}" method="post" role="form">
                            @csrf

                            <div class="box-body">
                                @foreach ($spellHelper->getSpells($selectedDominion->race, 'self')->chunk(4) as $spells)
                                    <div class="row">
                                        @foreach ($spells as $spell)
                                            <div class="col-xs-6 col-md-3 text-center">
                                                @php
                                                    $canCast = $spellCalculator->canCast($selectedDominion, $spell);
                                                    $cooldownHours = $spellCalculator->getSpellCooldown($selectedDominion, $spell);
                                                    $isActive = $selectedDominion->spells->contains($spell);
                                                    $buttonStyle = ($isActive ? 'btn-success' : 'btn-primary');
                                                @endphp
                                                <div class="form-group">
                                                    <button type="submit" name="spell" value="{{ $spell->key }}" class="btn {{ $buttonStyle }} btn-block" {{ $selectedDominion->isLocked() || !$canCast || $cooldownHours || ($selectedDominion->protection_ticks_remaining && $spell->hasPerk('invalid_protection')) || (!$isBlackGuard && in_array('chaos-league', $spell->races)) ? 'disabled' : null }}>
                                                        {{ $spell->name }}
                                                    </button>
                                                    <p style="margin: 5px 0;">{{ $spellHelper->getSpellDescription($spell) }}</p>
                                                    <small>
                                                        @if ($canCast)
                                                            Mana cost: <span class="text-success">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                        @else
                                                            Mana cost: <span class="text-danger">{{ number_format($spellCalculator->getManaCost($selectedDominion, $spell)) }}</span><br/>
                                                        @endif
                                                        @if ($isActive)
                                                            ({{ $spellCalculator->getSpellDurationRemaining($selectedDominion, $spell) }} hours remaining)<br/>
                                                        @else
                                                            Lasts {{ $spellCalculator->getSpellDuration($selectedDominion, $spell) }} hours<br/>
                                                        @endif
                                                        @if ($cooldownHours)
                                                            (<span class="text-danger">{{ $cooldownHours }} hours until recast</span>)<br/>
                                                        @endif
                                                        @if (!empty($spell->races))
                                                            Racial<br/>
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
                    <p>Here you may cast spells which temporarily benefit your dominion or hinder opposing dominions. You can also perform information gathering operations with magic.</p>
                    <p>Self spells last for <b>12 hours</b>, unless stated otherwise while Black Op spells last for <b>8 hours</b> outside of war, <b>10 hours</b> when at war, and <b>12 hours</b> when at mutual war.</p>
                    <p>Any obtained data after successfully casting an information gathering spell gets posted to the <a href="{{ route('dominion.op-center') }}">Op Center</a> for your realmies.</p>
                    <p>War and black ops cannot be performed until the 4th day of the round.<p>
                    <p><b>Friendly spells</b> can only be cast by your realm's Grand Magister or Court Mage.</p>
                    <p>Casting spells spends some wizard strength (2% for info, 4% for friendly, otherwise 5%), but it regenerates 4% every hour. You may only cast spells at or above 30% strength.</p>
                    <p>You have {{ number_format($selectedDominion->resource_mana) }} mana and {{ sprintf("%.4g", $selectedDominion->wizard_strength) }}% wizard strength.</p>
                </div>
            </div>
        </div>

    </div>
    <div class="row">

        <div class="col-md-12 col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="ra ra-burning-embers"></i> Spells affecting your dominion</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <colgroup>
                        <col width="150">
                        <col>
                        <col width="100">
                        <col width="200">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Spell</th>
                            <th>Effect</th>
                            <th class="text-center">Duration</th>
                            <th class="text-center">Cast By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($spellCalculator->getActiveSpells($selectedDominion) as $activeSpell)
                            <tr>
                                <td>{{ $activeSpell->spell->name }}</td>
                                <td>{{ $spellHelper->getSpellDescription($activeSpell->spell) }}</td>
                                <td class="text-center">{{ $activeSpell->duration }}</td>
                                <td class="text-center">
                                    @if ($activeSpell->cast_by_dominion_id == $selectedDominion->id || $selectedDominion->realm->dominions->pluck('id')->contains($activeSpell->cast_by_dominion_id) || $selectedDominion->getSpellPerkValue('surreal_perception'))
                                        <a href="{{ route('dominion.realm', $activeSpell->castByDominion->realm->number) }}">{{ $activeSpell->castByDominion->name }} (#{{ $activeSpell->castByDominion->realm->number }})</a>
                                    @else
                                        <em>Unknown</em>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                var revengeStatus = $(this).find(":selected").data('revenge');
                var guardStatus = $(this).find(":selected").data('guard');
                var friendlyStatus = $(this).find(":selected").data('friendly');
                if (guardStatus == 1) {
                    $('.war-non-chaos').hide();
                    $('.war-chaos').show();
                } else {
                    $('.war-chaos').hide();
                    $('.war-non-chaos').show();
                }
                if (!friendlyStatus && (warStatus == 1 || revengeStatus == 1 || guardStatus == 1)) {
                    $('.war-spell').removeClass('disabled');
                } else {
                    $('.war-spell').addClass('disabled');
                }
                if (friendlyStatus) {
                    $('.friendly-spell').removeClass('disabled');
                    $('.hostile-spell').addClass('disabled');
                } else {
                    $('.friendly-spell').addClass('disabled');
                    $('.hostile-spell').removeClass('disabled');
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
            } else if (war == 1) {
                warStatus = '<div class="pull-left">&nbsp;|&nbsp;<span class="text-red">WAR</span></div>';
            } else if (guard == 1) {
                warStatus = '<div class="pull-left">&nbsp;|&nbsp;<span class="text-red">SHADOW LEAGUE</span></div>';
            } else if (revenge == 1) {
                warStatus = '<div class="pull-left">&nbsp;|&nbsp;<span class="text-red">REVENGE</span></div>';
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
