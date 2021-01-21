@extends('layouts.master')

@section('page-header', "Register to round {$round->number} ({$round->league->description})")

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Register to round {{ $round->name }} (#{{ $round->number }})</h3>
        </div>
        <form action="{{ route('round.register', $round) }}" method="post" class="form-horizontal" role="form">
            @csrf

            <div class="box-body">

                <!-- Dominion Name -->
                <div class="form-group">
                    <label for="dominion_name" class="col-sm-3 control-label">Dominion Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="dominion_name" id="dominion_name" class="form-control" placeholder="Dominion Name" value="{{ old('dominion_name') }}" required autofocus>
                        <p class="help-block">Your dominion name is shown when viewing and interacting with other players. Must contain 3 consecutive alphanumeric characters.</p>
                    </div>
                </div>

                <!-- Ruler Name -->
                <div class="form-group">
                    <label for="ruler_name" class="col-sm-3 control-label">Ruler Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="ruler_name" id="ruler_name" class="form-control" placeholder="{{ Auth::user()->display_name }}" value="{{ old('ruler_name') }}">
                        <p class="help-block">This is your personal alias in the round which will be shown to your realmies. Defaults to your display name '{{ Auth::user()->display_name }}' if omitted.</p>
                    </div>
                </div>

                <!-- Race -->
                <div class="form-group">
                    <label for="race" class="col-sm-3 control-label">Race</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="text-center">
                                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Good Aligned Races</h4>
                                </div>
                                @php($i = 0)
                                @foreach ($races->filter(function ($race) { return $race->playable && $race->alignment === 'good'; }) as $race)
                                    @if($i % 2 == 0)
                                        <div class="row">
                                    @endif
                                            <div class="col-md-6">

                                                <label class="btn btn-block" style="border: 1px solid #d2d6de; margin: 5px 0px; white-space: normal;">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            {!! $raceHelper->getOverallDifficultyHtml($race->overall_difficulty) !!}
                                                            <h4>
                                                                <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off" {{ (old('race') == $race->id) ? 'checked' : null }} required>
                                                                <strong>{{ $race->name }}</strong>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <p>
                                                                Attacker: {!! $raceHelper->getDifficultyString($race->attacker_difficulty) !!}
                                                            </p>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <p>
                                                                Explorer: {!! $raceHelper->getDifficultyString($race->explorer_difficulty) !!}
                                                            </p>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <p>
                                                                Converter: {!! $raceHelper->getDifficultyString($race->converter_difficulty) !!}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-5 text-left">
                                                            <ul>
                                                                @foreach ($race->perks as $perk)
                                                                    <li>{!! $raceHelper->getPerkDescriptionHtml($perk) !!}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            {!! $race->description !!}
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                    @if($i % 2)
                                        </div>
                                    @endif
                                    @php($i++)
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="text-center">
                                    <h4 style="border-bottom: 1px solid #f4f4f4; margin-top: 0; padding: 10px 0">Evil Aligned Races</h4>
                                </div>
                                @php($i = 0)
                                @foreach ($races->filter(function ($race) { return $race->playable && $race->alignment === 'evil'; }) as $race)
                                    @if($i % 2 == 0)
                                        <div class="row">
                                    @endif
                                            <div class="col-md-6">

                                                <label class="btn btn-block" style="border: 1px solid #d2d6de; margin: 5px 0px; white-space: normal;">
                                                    <div class="row">
                                                        <div class="col-lg-12">
                                                            {!! $raceHelper->getOverallDifficultyHtml($race->overall_difficulty) !!}
                                                            <h4>
                                                                <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off" {{ (old('race') == $race->id) ? 'checked' : null }} required>
                                                                <strong>{{ $race->name }}</strong>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <p>
                                                                Attacker: {!! $raceHelper->getDifficultyString($race->attacker_difficulty) !!}
                                                            </p>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <p>
                                                                Explorer: {!! $raceHelper->getDifficultyString($race->explorer_difficulty) !!}
                                                            </p>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <p>
                                                                Converter: {!! $raceHelper->getDifficultyString($race->converter_difficulty) !!}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-5 text-left">
                                                            <ul>
                                                                @foreach ($race->perks as $perk)
                                                                    <li>{!! $raceHelper->getPerkDescriptionHtml($perk) !!}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                        <div class="col-lg-7">
                                                            {!! $race->description !!}
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                            @if($i % 2)
                                        </div>
                                    @endif
                                    @php($i++)
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Realm -->
                <div class="form-group">
                    <label for="realm" class="col-sm-3 control-label">Realm</label>
                    <div class="col-sm-9">
                        <select name="realm_type" id="realm_type" class="form-control" required>
                            <option value="random" {{ (old('realm_type') === 'random') ? 'selected' : null }}>Put me in a random realm</option>
                            <option value="join_pack" {{ (old('realm_type') === 'join_pack') ? 'selected' : null }}>Join an existing pack</option>
                            <option value="create_pack" {{ (old('realm_type') === 'create_pack') ? 'selected' : null }}>Create a new pack</option>
                        </select>
                        <p class="help-block">
                            <span class="text-danger">If you choose to join/create a pack, you will not be able to change your selection after registration.</span>
                        </p>
                    </div>
                </div>

                <!-- Pack Name -->
                <div class="form-group create-pack-only join-pack-only" style="display: none;">
                    <label for="pack_name" class="col-sm-3 control-label">Pack Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="pack_name" id="pack_name" class="form-control" placeholder="Pack Name" value="{{ old('pack_name') }}">
                        <p class="help-block create-pack-only">This is the name of your pack. This will be recorded and will eventually be shown in Valhalla.</p>
                        <p class="help-block join-pack-only">You need the pack name and password from the player whose pack you want to join.</p>
                    </div>
                </div>

                <!-- Pack Password -->
                <div class="form-group create-pack-only join-pack-only" style="display: none;">
                    <label for="pack_password" class="col-sm-3 control-label">Pack Password</label>
                    <div class="col-sm-9">
                        <input type="text" name="pack_password" id="pack_password" class="form-control" placeholder="Pack Password" value="{{ old('pack_password') }}">
                        <p class="help-block create-pack-only">Your packies need both your pack name and pack password in order to join.</p>
                    </div>
                </div>

                <!-- Pack Size (create only) -->
                <div class="form-group create-pack-only" style="display: none;">
                    <label for="pack_size" class="col-sm-3 control-label">Pack Size</label>
                    <div class="col-sm-9">
                        <select name="pack_size" id="pack_size" class="form-control">
                            @for ($i = 2; $i <= $round->pack_size; $i++)
                                @if ($i != 3)
                                <option value="{{ $i }}" {{ (old('pack_size') == $i) ? 'selected' : null }}>{{ $i }}</option>
                                @endif
                            @endfor
                        </select>
                        <p class="help-block">
                            The amount of players that will be in your pack (including yourself).<br/>
                            <span class="text-danger">Packs of size <b>{{ $round->pack_size }}</b> require each player to select a unique race.</span>
                        </p>
                    </div>
                </div>

                <!-- Rules -->
                <div class="form-group">
                    <label class="col-sm-3 control-label">Rules</label>
                    <div class="col-sm-9">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="agreement_rules" name="agreement_rules" />
                                I will adhere to the rules described in the OpenDominion <a href="#" data-toggle="modal" data-target="#user-agreement">User Agreement</a>.
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary" id="register_submit" disabled>Register</button>
            </div>

        </form>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="user-agreement" tabindex="-1" role="dialog" aria-labelledby="user-agreement-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="user-agreement-label">User Agreement</h4>
            </div>
            <div class="modal-body">
                @include('partials.user-agreement')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
            </div>
        </div>
    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {
            var realmTypeEl = $('#realm_type');
            var createPackOnlyEls = $('.create-pack-only');
            var joinPackOnlyEls = $('.join-pack-only');

            function updatePackInputs() {
                var realmTypeOption = realmTypeEl.find(':selected');

                if (realmTypeOption.val() === 'join_pack') {
                    createPackOnlyEls.hide();
                    joinPackOnlyEls.show();

                } else if (realmTypeOption.val() === 'create_pack') {
                    joinPackOnlyEls.hide();
                    createPackOnlyEls.show();

                } else {
                    createPackOnlyEls.hide();
                    joinPackOnlyEls.hide();
                }
            }

            realmTypeEl.on('change', updatePackInputs);

            updatePackInputs();

            $('#agreement_rules').change(function() {
                if ($(this).is(":checked")) {
                    $('#register_submit').prop('disabled', false);
                } else {
                    $('#register_submit').prop('disabled', true);
                }
            });
        })(jQuery);
    </script>
@endpush
