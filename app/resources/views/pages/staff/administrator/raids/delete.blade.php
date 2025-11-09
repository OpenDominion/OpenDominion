@extends('layouts.staff')

@section('page-header', 'Delete Raid')

@section('content')
    <div class="box box-danger">
        <div class="box-header with-border">
            <h3 class="box-title">Confirm Deletion</h3>
        </div>
        <div class="box-body">
            <p class="lead">Are you sure you want to delete this raid?</p>

            <div class="alert alert-danger">
                <h4><i class="icon fa fa-ban"></i> Warning!</h4>
                Deleting this raid will also delete:
                <ul>
                    <li><strong>{{ $raid->objectives->count() }} objective(s)</strong></li>
                    <li><strong>{{ $raid->objectives->sum(function($obj) { return $obj->tactics->count(); }) }} tactic(s)</strong></li>
                    <li><strong>All associated contributions</strong></li>
                </ul>
                <p class="text-bold">This action cannot be undone!</p>
            </div>

            <table class="table table-bordered">
                <tr>
                    <th width="200">Raid Name</th>
                    <td>{{ $raid->name }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{!! $raid->description !!}</td>
                </tr>
                <tr>
                    <th>Round</th>
                    <td>{{ $raid->round->name }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{!! app(OpenDominion\Helpers\RaidHelper::class)->getStatusLabel($raid->status) !!}</td>
                </tr>
                <tr>
                    <th>Duration</th>
                    <td>Day {{ $raid->round->daysInRound($raid->start_date) }} to Day {{ $raid->round->daysInRound($raid->end_date) }} ({{ $raid->start_date->format('M d, Y H:i') }} to {{ $raid->end_date->format('M d, Y H:i') }})</td>
                </tr>
            </table>
        </div>
        <div class="box-footer">
            <form action="{{ route('staff.administrator.raids.delete', $raid) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Yes, Delete This Raid
                </button>
            </form>
            <a href="{{ route('staff.administrator.raids.show', $raid) }}" class="btn btn-default">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
@endsection
