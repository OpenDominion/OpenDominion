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
                        <input type="text" name="dominion_name" id="dominion_name" class="form-control" placeholder="Dominion Name" required>
                    </div>
                </div>

                <!-- Race -->
                <div class="form-group">
                    <label for="race" class="col-sm-3 control-label">Race</label>
                    <div class="col-sm-9">
                        <select name="race" class="form-control" required>
                            @foreach ($races as $race)
                                <option value="{{ $race->id }}">{{ $race->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Realm -->
                <div class="form-group">
                    <label for="realm" class="col-sm-3 control-label">Realm</label>
                    <div class="col-sm-9">
                        <select name="realm" class="form-control" required>
                            <option value="random">Put me in a random realm</option>
                            <option value="pack">Pack</option>
                        </select>
                    </div>
                </div>

                <!-- Pack -->
                <div class="form-group">
                    <label for="pack_password" class="col-sm-3 control-label">Pack password</label>
                    <div class="col-sm-9">
                        <input type="password" name="pack_password" id="pack_password" class="form-control" placeholder="Pack password">
                    </div>
                    <label for="create_pack" class="col-sm-3 control-label">Create new pack</label>
                    <div class="col-sm-9">
                        <input type="checkbox" name="create_pack" id="create_pack" >
                    </div>
                    <label for="pack_size" class="col-sm-3 control-label">Pack size</label>
                    <div class="col-sm-9">
                        <select name="pack_size" class="form-control">
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                    </div>
                </div>

            </div>

            <div class="box-footer">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>

        </form>
    </div>
@endsection
