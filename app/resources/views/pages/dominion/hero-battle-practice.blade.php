@extends('layouts.master')

@section('page-header', 'Hero Battles')

@section('content')
    <div class="row">

        <div class="col-12">
            <form action="{{ route('dominion.heroes.battles.practice') }}" method="post" role="form">
                @csrf
                <div class="card card-primary">
                    <div class="card-header">
                        <span class="card-title"><i class="ra ra-axe"></i> Practice Battles</span>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table">
                            <colgroup>
                                <col width="5%">
                                <col width="35%">
                                <col width="60%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Encounter</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="radio" id="encounter-default" name="encounter" value="default" checked /></td>
                                    <td><label for="encounter-default" style="font-weight: normal;">Evil Twin</label></td>
                                    <td>Clone with your current stats</td>
                                </tr>
                                @foreach ($encounters as $key => $encounter)
                                    @php($needsCompanion = $encounter->minPartySize() > 1 && $companions->isEmpty())
                                    <tr>
                                        <td><input type="radio" id="encounter-{{ $key }}" name="encounter" value="{{ $key }}" data-party-size="{{ $encounter->maxPartySize() }}" data-min-party-size="{{ $encounter->minPartySize() }}" @disabled($needsCompanion) /></td>
                                        <td>
                                            <label for="encounter-{{ $key }}" style="font-weight: normal;">{{ $encounter->name() }}</label>
                                            @if ($encounter->maxPartySize() > 1)
                                                <span class="badge text-bg-info">Party of {{ $encounter->maxPartySize() }}</span>
                                            @endif
                                            @if ($needsCompanion)
                                                <small class="text-muted">Needs a realmmate's hero</small>
                                            @endif
                                        </td>
                                        <td>{{ $encounter->source() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($companions->isNotEmpty())
                            <div id="practice-companion" class="d-none">
                                <label for="companion_hero_id" class="form-label">Bring a realmmate's hero</label>
                                <select name="companion_hero_id" id="companion_hero_id" class="form-select">
                                    <option value="" data-go-alone>Go alone</option>
                                    @foreach ($companions as $companion)
                                        @php($loadoutSummary = $heroHelper->getLoadoutSummary($companion))
                                        <option value="{{ $companion->id }}">{{ $companion->name }} ({{ $companion->dominion->name }}){{ $loadoutSummary !== '' ? " - {$loadoutSummary}" : null }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary" type="submit">Start Practice Battle</button>
                    </div>
                </div>
            </form>

        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">How Hero Battles Work</span>
                </div>
                <div class="card-body">
                    @include('partials.dominion.hero-combat')
                </div>
            </div>
        </div>

    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var encounters = $('input[type=radio][name=encounter]');
            var companionSection = $('#practice-companion');
            var companionSelect = $('#companion_hero_id');

            function toggleCompanion() {
                var checked = encounters.filter(':checked');
                var isParty = checked.data('party-size') > 1;
                var companionRequired = checked.data('min-party-size') > 1;
                companionSection.toggleClass('d-none', !isParty);
                companionSelect.find('[data-go-alone]').prop('disabled', companionRequired);
                if (!isParty) {
                    companionSelect.val('');
                } else if (companionRequired && !companionSelect.val()) {
                    companionSelect.val(companionSelect.find('option:not([data-go-alone])').first().val());
                }
            }

            encounters.change(toggleCompanion);
            toggleCompanion();
        })(jQuery);
    </script>
@endpush
