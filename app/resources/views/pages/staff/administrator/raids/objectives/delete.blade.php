@extends('layouts.staff')

@section('page-header', 'Delete Objective')

@section('content')
    <div class="card border-danger">
        <div class="card-header">
            <h3 class="card-title">Confirm Deletion</h3>
        </div>
        <div class="card-body">
            <p class="lead">Are you sure you want to delete this objective?</p>

            <div class="alert alert-danger">
                <h4><i class="icon fa fa-ban"></i> Warning!</h4>
                Deleting this objective will also delete:
                <ul>
                    <li><strong>{{ $objective->tactics->count() }} tactic(s)</strong></li>
                    <li><strong>All associated contributions</strong></li>
                </ul>
                <p class="text-bold">This action cannot be undone!</p>
            </div>

            <table class="table table-bordered">
                <tr>
                    <th width="200">Objective Name</th>
                    <td>{{ $objective->name }}</td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td>{!! $objective->description !!}</td>
                </tr>
                <tr>
                    <th>Raid</th>
                    <td>{{ $raid->name }}</td>
                </tr>
                <tr>
                    <th>Order</th>
                    <td>{{ $objective->order }}</td>
                </tr>
                <tr>
                    <th>Score Required</th>
                    <td>{{ number_format($objective->score_required) }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{!! app(OpenDominion\Helpers\RaidHelper::class)->getStatusLabel($objective->status, true) !!}</td>
                </tr>
                <tr>
                    <th>Duration</th>
                    <td>Day {{ $raid->round->daysInRound($objective->start_date) }} to Day {{ $raid->round->daysInRound($objective->end_date) }} ({{ $objective->start_date->format('M d, Y H:i') }} to {{ $objective->end_date->format('M d, Y H:i') }})</td>
                </tr>
            </table>
        </div>
        <div class="card-footer">
            <form action="{{ route('staff.administrator.raids.objectives.delete', [$raid, $objective]) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Yes, Delete This Objective
                </button>
            </form>
            <a href="{{ route('staff.administrator.raids.objectives.show', [$raid, $objective]) }}" class="btn btn-secondary">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
@endsection
