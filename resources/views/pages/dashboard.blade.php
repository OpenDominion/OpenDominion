@extends('layouts.master')

@section('page-header', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <p>Welcome back, <b>{{ Auth::user()->display_name }}</b>.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="ra ra-capitol ra-fw"></i> Dominions
                </div>
                <div class="panel-body">
                    @if ($dominions->isEmpty())
                        <p>You have no active dominions</p>
                    @else
                        todo
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-clock-o fa-fw"></i> Rounds
                </div>
                <div class="panel-body">
                    @if ($rounds->isEmpty())
                        <p>There are currently no active rounds.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped">
                                <colgroup>
                                    <col width="40">
                                    <col>
                                    <col width="100">
                                    <col width="100">
                                    <col width="100">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th class="text-center">#</th>
                                        <th>Name</th>
                                        <th class="text-center">Start</th>
                                        <th class="text-center">Duration</th>
                                        <th class="text-center">Register</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rounds->all() as $round)
                                        <tr>
                                            <td class="text-center">{{ $round->number }}</td>
                                            <td>
                                                {{ $round->name }}
                                                <abbr class="text-muted" title="{{ $round->league->description }}">({{ $round->league->key }})</abbr>
                                            </td>
                                            <td class="text-center">
                                                @if ($round->start_date > new DateTime('today'))
                                                    <abbr title="Starting at {{ $round->start_date }}">{{ $round->start_date->diffInDays(\Carbon\Carbon::now()) }} day(s)</abbr>
                                                @else
                                                    <span class="text-warning">Started!</span>
                                                    {{-- todo: Show current round milestone (mid, end etc) with appropriate text color --}}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <abbr title="Ending at {{ $round->end_date }}">{{ $round->start_date->diffInDays($round->end_date) }} days</abbr>
                                            </td>
                                            <td class="text-center">
                                                <a href="#" class="btn btn-primary btn-xs">Register</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection
