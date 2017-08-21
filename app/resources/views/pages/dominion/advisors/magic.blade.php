@extends('layouts.master')

@section('page-header', 'Magic Advisor')

@section('content')
    @include('partials.dominion.advisor-selector')

    <div class="row">

        <div class="col-md-12 col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="ra ra-burning-embers"></i> Spells affecting your dominion</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-hover">
                        <colgroup>
                            <col>
                            <col width="100">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Spell</th>
                                <th class="text-center">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- todo: self-cast magic system --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-md-3">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Information</h3>
                </div>
                <div class="box-body">
                    <p>The magic advisor tells you the current spells affecting your dominion and their duration.</p>
                </div>
            </div>
        </div>

    </div>

@endsection
