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
                        <input type="text" name="dominion_name" id="dominion_name" class="form-control" placeholder="Dominion Name" required autofocus>
                        <p class="help-block">Your dominion name is shown when viewing and interacting with other players.</p>
                    </div>
                </div>

                <!-- Ruler Name -->
                <div class="form-group">
                    <label for="ruler_name" class="col-sm-3 control-label">Ruler Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="ruler_name" id="ruler_name" class="form-control" placeholder="{{ Auth::user()->display_name }}">
                        <p class="help-block">This is your personal alias in the round which will be shown to your realmies. Defaults to your display name '{{ Auth::user()->display_name }}' if omitted.</p>
                    </div>
                </div>

                <!-- Race -->
                <div class="form-group">
                    <label for="race" class="col-sm-3 control-label">Race</label>
                    <div class="col-sm-9">
                        <div class="row">

                            <div class="col-xs-6">
                                <div class="text-center">
                                    <strong>Good Aligned Races</strong>
                                </div>
                                <div class="row">
                                    @foreach ($races->filter(function ($race) { return $race->alignment === 'good'; }) as $race)
                                        <div class="col-xs-6">
                                            <label class="btn btn-block">
                                                <p>
                                                    <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off">
                                                    <strong>{{ $race->name }}</strong>
                                                </p>
                                                {!! $raceHelper->getRaceDescriptionHtml($race) !!}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-xs-6">
                                <div class="text-center">
                                    <strong>Evil Aligned Races</strong>
                                </div>
                                <div class="row">
                                    @foreach ($races->filter(function ($race) { return $race->alignment === 'evil'; }) as $race)
                                        <div class="col-xs-6">
                                            <label class="btn btn-block">
                                                <p>
                                                    <input type="radio" name="race" value="{{ $race->id }}" autocomplete="off">
                                                    <strong>{{ $race->name }}</strong>
                                                </p>
                                                {!! $raceHelper->getRaceDescriptionHtml($race) !!}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Realm -->
                <div class="form-group">
                    <label for="realm" class="col-sm-3 control-label">Realm</label>
                    <div class="col-sm-9">
                        <select name="realm" id="realm" class="form-control" required>
                            <option value="random">Put me in a random realm</option>
                            <option value="join_pack">Join an existing pack</option>
                            <option value="create_pack">Create a new pack</option>
                        </select>
                    </div>
                </div>

                <!-- Pack Name -->
                <div class="form-group create-pack-only join-pack-only" style="display: none;">
                    <label for="pack_name" class="col-sm-3 control-label">Pack Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="pack_name" id="pack_name" class="form-control" placeholder="Pack Name">
                        <p class="help-block create-pack-only">This is the name of your pack. This will be recoded and will eventually be shown in Valhalla.</p>
                        <p class="help-block join-pack-only">You need the pack name and password from the player whose pack you want to join.</p>
                    </div>
                </div>

                <!-- Pack Password -->
                <div class="form-group create-pack-only join-pack-only" style="display: none;">
                    <label for="pack_password" class="col-sm-3 control-label">Pack Password</label>
                    <div class="col-sm-9">
                        <input type="text" name="pack_password" id="pack_password" class="form-control" placeholder="Pack Password">
                        <p class="help-block create-pack-only">Your packies need both your pack name and pack password in order to join.</p>
                    </div>
                </div>

                <!-- Pack Size (create only) -->
                <div class="form-group create-pack-only" style="display: none;">
                    <label for="pack_size" class="col-sm-3 control-label">Pack Size</label>
                    <div class="col-sm-9">
                        {{--<input type="number" name="pack_size" id="pack_size" class="form-control" min="2" max="3" placeholder="2">--}}
                        <select name="pack_size" id="pack_size" class="form-control">
                            @foreach (range(2, 3) as $i)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endforeach
                        </select>
                        <p class="help-block">The amount of players that will be in your pack (including yourself).</p>
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>

        </form>
    </div>
@endsection

@push('inline-scripts')
    <script type="text/javascript">
        (function ($) {

            var createPackOnlyEls = $('.create-pack-only');
            var joinPackOnlyEls = $('.join-pack-only');

            $('#realm').change(function (e) {

                switch (this.value) {
                    case 'random':
                        createPackOnlyEls.hide();
                        joinPackOnlyEls.hide();
                        break;

                    case 'join_pack':
                        createPackOnlyEls.hide();
                        joinPackOnlyEls.show();
                        break;

                    case 'create_pack':
                        joinPackOnlyEls.hide();
                        createPackOnlyEls.show();
                        break;
                }

                // console.log([this, this.value]);

            });

        })(jQuery);
    </script>
@endpush
