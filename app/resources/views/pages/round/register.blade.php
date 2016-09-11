@extends('layouts.master')

@section('page-header', "Register to round {$round->number} ({$round->league->description})")

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-5 col-lg-4">
            <form action="{{ route('round.register', $round) }}" method="post" role="form">
                {{ csrf_field() }}
                <fieldset>
                    <div class="form-group">
                        <input type="text" class="form-control" name="dominion_name" placeholder="Dominion Name" autofocus>
                    </div>
                    <div class="form-group">
                        <select class="form-control" name="race" required>
                            @foreach ($races as $race)
                                <option value="{{ $race->id }}">{{ $race->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control" name="realm" required>
                            <option value="random">Put me in a random realm</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-lg btn-success btn-block">Register</button>
                </fieldset>
            </form>
        </div>
    </div>
@endsection
