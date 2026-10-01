@extends('layouts.staff')

@section('page-header', 'Edit Round Perk')

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="card-title">Edit Perk for: {{ $round->name }} (Round #{{ $round->number }})</span>
        </div>
        <form action="{{ route('staff.administrator.rounds.perks.edit', [$round, $perk]) }}" method="POST">
            @csrf

            @include('pages.staff.administrator.rounds.perks.partials.form', ['perk' => $perk])

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-check"></i> Save Perk
                </button>
                <a href="{{ route('staff.administrator.rounds.show', $round) }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
