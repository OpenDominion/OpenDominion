@extends('layouts.staff')

@section('page-header', 'Delete Hero Tournament')

@section('content')
    <div class="box box-danger">
        <div class="box-header with-border">
            <h3 class="box-title">Delete Hero Tournament: {{ $tournament->name }}</h3>
        </div>
        <div class="box-body">
            <p>Are you sure you want to delete this hero tournament?</p>
            <table class="table table-condensed">
                <tr>
                    <th width="200">Name</th>
                    <td>{{ $tournament->name }}</td>
                </tr>
                <tr>
                    <th>Round</th>
                    <td>{{ $tournament->round->name }}</td>
                </tr>
                <tr>
                    <th>Participants</th>
                    <td>{{ $tournament->participants->count() }}</td>
                </tr>
                <tr>
                    <th>Battles</th>
                    <td>{{ $tournament->battles->count() }}</td>
                </tr>
            </table>
            <p class="text-red"><strong>This action cannot be undone.</strong> All participants will be removed.</p>
        </div>
        <div class="box-footer">
            <form action="{{ route('staff.administrator.hero-tournaments.delete', $tournament) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Delete Tournament
                </button>
            </form>
            <a href="{{ route('staff.administrator.hero-tournaments.show', $tournament) }}" class="btn btn-default">
                <i class="fa fa-times"></i> Cancel
            </a>
        </div>
    </div>
@endsection
