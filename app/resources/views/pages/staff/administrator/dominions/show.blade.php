@extends('layouts.staff')

@section('page-header', "Dominion: {$dominion->name}")

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="box box-info">
                <pre>{{ print_r(json_decode($dominion), true) }}</pre>
            </div>
        </div>
    </div>
@endsection
