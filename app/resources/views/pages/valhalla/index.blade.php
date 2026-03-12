@extends('layouts.topnav')

@section('content')
    <div class="card">
        <div class="card-body">
            <p>It is in Valhalla that you will be able to see the brave warriors of the past that have become revered by warriors of the present. These souls battled long and hard, and were the best of their generations.</p>
        </div>
    </div>

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="ra ra-angel-wings"></i> Valhalla</h3>
            <div class="float-end">
                <form method="GET" action="{{ route('valhalla.user.search') }}" class="form-inline" style="white-space: nowrap;">
                    <div class="form-group form-group-sm">
                        <input type="text" class="form-control form-control-sm" name="query" placeholder="Username" />
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
            </div>
        </div>
        <div class="card-body table-responsive">
            <div class="row">
                @foreach ($leagues as $league)
                    <div class="col-md-12 col-lg-6">
                        <h4>
                            {{ $league->description }} - 
                            <small>
                                <a href="{{ route('valhalla.league', $league->id) }}">
                                    lifetime standings
                                </a>
                            </small>
                        </h4>
                        <table class="table table-striped">
                            <colgroup>
                                <col width="50">
                                <col>
                                <col width="250">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="text-center">Round</th>
                                    <th>Name</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($league->rounds->sortByDesc('created_at') as $round)
                                    <tr>
                                        <td class="text-center">{{ number_format($round->number) }}</td>
                                        <td>
                                            @if ($round->isActive())
                                                {{ $round->name }}
                                                <span class="badge text-bg-info">Active</span>
                                            @elseif (!$round->hasStarted())
                                                {{ $round->name }}
                                                <span class="badge text-bg-warning">Not yet started</span>
                                            @else
                                                <a href="{{ route('valhalla.round', $round) }}">{{ $round->name }}</a>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $round->start_date->toFormattedDateString() }}
                                            to
                                            {{ $round->end_date->toFormattedDateString() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
