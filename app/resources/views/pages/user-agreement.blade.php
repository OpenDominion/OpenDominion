@extends('layouts.topnav')

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        User Agreement
                    </h3>
                </div>
                <div class="box-body">
                    @include('partials.user-agreement')
                </div>
            </div>
        </div>
    </div>
@endsection
