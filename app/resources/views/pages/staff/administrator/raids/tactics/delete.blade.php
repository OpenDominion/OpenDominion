@extends('layouts.staff')

@section('page-header', 'Delete Tactic')

@section('content')
    <div class="card border-danger">
        <div class="card-header">
            <h3 class="card-title">Confirm Deletion</h3>
        </div>
        <div class="card-body">
            <p class="lead">Are you sure you want to delete this tactic?</p>

            <div class="alert alert-warning">
                <h4><i class="icon fa fa-warning"></i> Warning!</h4>
                <p class="text-bold">This action cannot be undone!</p>
            </div>

            <table class="table table-bordered">
                <tr>
                    <th width="200">Tactic Name</th>
                    <td>{{ $tactic->name }}</td>
                </tr>
                <tr>
                    <th>Type</th>
                    <td><span class="badge text-bg-primary">{{ ucfirst($tactic->type) }}</span></td>
                </tr>
                <tr>
                    <th>Objective</th>
                    <td><a href="{{ route('staff.administrator.raids.objectives.show', [$raid, $objective]) }}">{{ $objective->name }}</a></td>
                </tr>
                <tr>
                    <th>Raid</th>
                    <td><a href="{{ route('staff.administrator.raids.show', $raid) }}">{{ $raid->name }}</a></td>
                </tr>
                <tr>
                    <th>Attributes</th>
                    <td>
                        <pre style="margin: 0;">{{ json_encode($tactic->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </td>
                </tr>
                @if (!empty($tactic->bonuses))
                    <tr>
                        <th>Bonuses</th>
                        <td>
                            <pre style="margin: 0;">{{ json_encode($tactic->bonuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </td>
                    </tr>
                @endif
            </table>
        </div>
        <div class="card-footer">
            <form action="{{ route('staff.administrator.raids.objectives.tactics.delete', [$raid, $objective, $tactic]) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Yes, Delete This Tactic
                </button>
            </form>
            <a href="{{ route('staff.administrator.raids.objectives.show', [$raid, $objective]) }}" class="btn btn-secondary">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
@endsection
