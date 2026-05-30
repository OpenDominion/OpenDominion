@extends('layouts.master')

@section('page-header', $raid->name)

@section('content')
    <div class="row">
        <div class="col-sm-12 col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <span class="card-title"><i class="ra ra-book-cover"></i> {{ $raid->name }}</span>
                    <div class="float-end">
                        <a href="{{ route('dominion.raids') }}" class="btn btn-sm btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back to Raids
                        </a>
                    </div>
                </div>
                <div class="card-body raid-story">
                    @if ($raid->extended_description)
                        {!! Str::markdown($raid->extended_description, ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}
                    @else
                        {!! $raid->description !!}
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card card-secondary">
                <div class="card-header">
                    <span class="card-title">Objectives</span>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @foreach ($raid->objectives->sortBy('order') as $objective)
                            <li class="mb-2">
                                <strong>{{ $objective->order }}.</strong>
                                <a href="{{ route('dominion.raids.objective', [$objective->id]) }}">
                                    {{ $objective->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('inline-styles')
    <style>
        .raid-story h2,
        .raid-story h3 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-family: Georgia, 'Times New Roman', serif;
        }
        .raid-story h2:first-child,
        .raid-story h3:first-child {
            margin-top: 0;
        }
        .raid-story p {
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 1rem;
            text-align: justify;
        }
        .raid-story hr {
            margin: 2.5rem 0;
            opacity: 0.5;
        }
    </style>
@endpush
