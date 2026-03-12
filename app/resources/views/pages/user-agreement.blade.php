@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        User Agreement
                    </h3>
                </div>
                <div class="card-body">
                    @include('partials.user-agreement')
                </div>
            </div>
        </div>
    </div>
@endsection
